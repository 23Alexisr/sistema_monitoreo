<?php

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\ObraResource;
use App\Filament\Resources\RequerimientoResource;
use App\Models\CategoriaMaterial;
use App\Models\Material;
use App\Models\Obra;
use App\Models\Requerimiento;
use App\Models\SubcategoriaMaterial;
use App\Models\TrabajoMaestro;
use App\Models\TrabajoMaterialSugerido;
use App\Support\PermisoRequerimiento;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class CrearRequerimiento extends Page
{
    use WithFileUploads;

    protected static string $resource = RequerimientoResource::class;

    protected static string $view = 'filament.resources.requerimiento-resource.pages.crear-requerimiento';

    protected static ?string $title = 'Pedir materiales';

    protected const LIMITE_BUSQUEDA = 20;

    protected const LIMITE_RECIENTES = 12;

    protected const LIMITE_TRABAJOS = 20;

    protected const LIMITE_TRABAJOS_RECIENTES = 8;

    public const ROLES_CREADORES = ['administrador', 'operario', 'jefe_proyectos', 'jefe_ssoma'];

    /**
     * 'material' (operario/encargado, con origen de checklist opcional y
     * sugerencias) o 'señaletica' (jefe_proyectos/jefe_ssoma/administrador,
     * sin checklist de por medio, catálogo agrupado por subcategoría).
     * Se fija al entrar a la página (query param `tipo`), no cambia con la
     * obra seleccionada.
     */
    public string $modoFlujo = 'material';

    public ?int $obraId = null;

    /**
     * Trabajo del catálogo general (trabajos_maestro) elegido para
     * precargar materiales sugeridos — sin relación con el checklist de
     * la obra actual. `checklist_item_id` en el requerimiento queda sin
     * usar por este flujo (ver enviar()).
     */
    public ?int $trabajoMaestroId = null;

    public string $busquedaTrabajo = '';

    public ?int $requerimientoOriginalId = null;

    public array $carrito = [];

    public array $cantidadesSugeridas = [];

    public string $busquedaCatalogo = '';

    public ?int $materialParaCantidadId = null;

    public ?string $cantidadTexto = null;

    public ?string $anchoPedidoTexto = null;

    public ?string $largoPedidoTexto = null;

    public bool $modalManualAbierto = false;

    public ?string $descripcionManual = null;

    public ?string $anchoManualTexto = null;

    public ?string $largoManualTexto = null;

    public $fotoReferenciaManual = null;

    public ?string $tipoManualSeleccionado = null;

    public ?string $cantidadManualTexto = null;

    public ?string $errorAnchoManual = null;

    public ?string $errorLargoManual = null;

    public bool $revisandoPedido = false;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasAnyRole(self::ROLES_CREADORES) ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->modoFlujo = request()->query('tipo') === 'señaletica' ? 'señaletica' : 'material';

        $user = auth()->user();
        $obraId = (int) (request()->query('obra_id') ?? 0);

        if (! $obraId) {
            $candidatas = $this->obrasCandidatas();

            if ($candidatas->count() === 1) {
                $obraId = $candidatas->first()->id;
            }
        }

        if ($obraId) {
            abort_unless(PermisoRequerimiento::puedeCrear($user, $obraId), 403);
        }

        $this->obraId = $obraId ?: null;
    }

    public function obrasCandidatas(): Collection
    {
        $user = auth()->user();

        if ($user->hasRole('administrador') || $user->hasAnyRole(['jefe_proyectos', 'jefe_ssoma'])) {
            return Obra::query()->whereNotIn('estado', ['finalizada', 'cancelada'])->orderBy('nombre')->get();
        }

        return Obra::query()
            ->whereHas('programaciones.empleado', fn ($q) => $q->where('user_id', $user->id))
            ->get()
            ->filter(fn (Obra $obra) => PermisoRequerimiento::puedeCrear($user, $obra->id))
            ->values();
    }

    public function getObra(): ?Obra
    {
        return $this->obraId ? Obra::find($this->obraId) : null;
    }

    public function seleccionarObra(int $obraId): void
    {
        abort_unless(PermisoRequerimiento::puedeCrear(auth()->user(), $obraId), 403);

        $this->obraId = $obraId;
        $this->trabajoMaestroId = null;
        $this->busquedaTrabajo = '';
        $this->requerimientoOriginalId = null;
        $this->carrito = [];
        $this->busquedaCatalogo = '';
    }

    /**
     * Búsqueda con filtro sobre el catálogo general de trabajos (mismo
     * patrón que el buscador de materiales/checklist), respetando el mismo
     * criterio universal/cliente-específico que el resto del catálogo.
     *
     * @return array{items: Collection<int, TrabajoMaestro>, total: ?int}
     */
    public function resultadosTrabajoMaestro(): array
    {
        if (! $this->obraId) {
            return ['items' => collect(), 'total' => null];
        }

        $clienteId = $this->getObra()?->cliente_id;

        $base = TrabajoMaestro::query()
            ->where('activo', true)
            ->where(fn (Builder $q) => $q->whereNull('cliente_id')->when($clienteId, fn (Builder $q2) => $q2->orWhere('cliente_id', $clienteId)))
            ->with(['categoria', 'subcategoria.categoria']);

        $termino = trim($this->busquedaTrabajo);

        if ($termino === '') {
            $items = (clone $base)->orderBy('descripcion')->limit(self::LIMITE_TRABAJOS_RECIENTES)->get();

            return ['items' => $items, 'total' => null];
        }

        $query = (clone $base)->where('descripcion', 'like', "%{$termino}%");

        $total = (clone $query)->count();
        $items = $query->orderBy('descripcion')->limit(self::LIMITE_TRABAJOS)->get();

        return ['items' => $items, 'total' => $total];
    }

    public function seleccionarTrabajoMaestro(?int $trabajoMaestroId): void
    {
        $this->trabajoMaestroId = $trabajoMaestroId ?: null;
        $this->cantidadesSugeridas = [];
    }

    public function sugerenciasPendientes(): Collection
    {
        if (! $this->trabajoMaestroId) {
            return collect();
        }

        return TrabajoMaterialSugerido::query()
            ->where('trabajo_maestro_id', $this->trabajoMaestroId)
            ->with('material')
            ->get()
            ->reject(fn (TrabajoMaterialSugerido $s) => isset($this->carrito[$s->material_id]))
            ->values();
    }

    public function agregarSugerido(int $materialId): void
    {
        $sugerido = TrabajoMaterialSugerido::where('material_id', $materialId)
            ->where('trabajo_maestro_id', $this->trabajoMaestroId)
            ->first();

        $cantidad = (float) ($this->cantidadesSugeridas[$materialId] ?? $sugerido?->cantidad_sugerida ?? 0);

        if ($cantidad <= 0) {
            return;
        }

        $this->carrito[$materialId] = [
            'material_id' => $materialId,
            'cantidad' => $cantidad,
            'es_sugerido' => true,
        ];

        Notification::make()->success()->title('Sugerencia agregada')->send();
    }

    public function requerimientosAnterioresDeObra(): Collection
    {
        if (! $this->obraId) {
            return collect();
        }

        return Requerimiento::query()
            ->where('obra_id', $this->obraId)
            ->orderByDesc('fecha_solicitud')
            ->limit(20)
            ->get();
    }

    public function basarEnAnterior(?int $requerimientoId): void
    {
        if (! $requerimientoId) {
            $this->requerimientoOriginalId = null;

            return;
        }

        $anterior = Requerimiento::with('items')->find($requerimientoId);

        if (! $anterior || $anterior->obra_id !== $this->obraId) {
            return;
        }

        $this->requerimientoOriginalId = $requerimientoId;

        foreach ($anterior->items as $item) {
            $clave = $item->material_id ?? ('manual-'.Str::uuid());

            $this->carrito[$clave] = [
                'material_id' => $item->material_id,
                'descripcion_manual' => $item->descripcion_manual,
                'medidas' => $item->medidas,
                'ancho_pedido' => $item->ancho_pedido ? (float) $item->ancho_pedido : null,
                'largo_pedido' => $item->largo_pedido ? (float) $item->largo_pedido : null,
                'foto_referencia' => $item->foto_referencia,
                'cantidad' => (float) $item->cantidad,
                'es_sugerido' => false,
                'tipo' => $item->material_id ? null : $anterior->tipo->value,
            ];
        }

        Notification::make()->success()->title('Items del pedido anterior cargados, puedes ajustarlos')->send();
    }

    /**
     * IDs de categorías/subcategorías cuyo nombre deriva a tipo Señalética
     * (mismo criterio que TipoRequerimiento::desdeCategoriaNombre). Las
     * tablas de categorías son chicas, se evalúan enteras en PHP en vez de
     * intentar replicar la normalización de acentos en SQL.
     */
    protected function idsCatalogoSenaletica(): array
    {
        $categoriaIds = CategoriaMaterial::all()
            ->filter(fn (CategoriaMaterial $c) => TipoRequerimiento::desdeCategoriaNombre($c->nombre) === TipoRequerimiento::Señaletica)
            ->pluck('id');

        $subcategoriaIds = SubcategoriaMaterial::whereIn('categoria_id', $categoriaIds)->pluck('id');

        return [$categoriaIds->all(), $subcategoriaIds->all()];
    }

    /**
     * Acota el catálogo al que corresponde según $modoFlujo: solo
     * señalética, o todo menos señalética (material/seguridad). Un
     * material tiene categoria_id XOR subcategoria_id, nunca ambos.
     */
    protected function aplicarFiltroFlujo(Builder $query): Builder
    {
        [$categoriaIds, $subcategoriaIds] = $this->idsCatalogoSenaletica();

        if ($this->modoFlujo === 'señaletica') {
            return $query->where(fn (Builder $q) => $q
                ->whereIn('categoria_id', $categoriaIds)
                ->orWhereIn('subcategoria_id', $subcategoriaIds));
        }

        return $query
            ->where(fn (Builder $q) => $q->whereNotIn('categoria_id', $categoriaIds)->orWhereNull('categoria_id'))
            ->where(fn (Builder $q) => $q->whereNotIn('subcategoria_id', $subcategoriaIds)->orWhereNull('subcategoria_id'));
    }

    public function resultadosBusqueda(): array
    {
        if (! $this->obraId) {
            return ['items' => collect(), 'total' => null];
        }

        $base = $this->aplicarFiltroFlujo(PermisoRequerimiento::materialesVisibles(auth()->user(), $this->obraId));
        $termino = trim($this->busquedaCatalogo);

        if ($termino === '') {
            $items = (clone $base)->orderBy('nombre')->limit(self::LIMITE_RECIENTES)->get();

            return ['items' => $items, 'total' => null];
        }

        $query = (clone $base)->where(fn ($q) => $q
            ->where('nombre', 'like', "%{$termino}%")
            ->orWhere('codigo', 'like', "%{$termino}%"));

        $total = (clone $query)->count();
        $items = $query->orderBy('nombre')->limit(self::LIMITE_BUSQUEDA)->get();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Catálogo de señalética agrupado por subcategoría (sin límite: son
     * catálogos chicos y finitos, a diferencia del de materiales general).
     * Los materiales sin subcategoría (categoría directa) caen en "General".
     *
     * @return Collection<string, Collection<int, Material>>
     */
    public function catalogoSenaleticaAgrupado(): Collection
    {
        if (! $this->obraId) {
            return collect();
        }

        $base = $this->aplicarFiltroFlujo(PermisoRequerimiento::materialesVisibles(auth()->user(), $this->obraId))
            ->with('subcategoria');

        $termino = trim($this->busquedaCatalogo);

        if ($termino !== '') {
            $base->where(fn ($q) => $q->where('nombre', 'like', "%{$termino}%")->orWhere('codigo', 'like', "%{$termino}%"));
        }

        return $base->orderBy('nombre')->get()
            ->groupBy(fn (Material $material) => $material->subcategoria?->nombre ?? 'General')
            ->sortKeys();
    }

    /**
     * Agrega de una sola vez todos los materiales de un grupo (subcategoría)
     * del catálogo de señalética, cantidad 1 c/u, sin pasar por el modal
     * individual. Los materiales que ya están en el carrito se dejan como
     * están (no se pisa la cantidad que el usuario ya haya puesto), y los
     * que no tienen medida fija en el catálogo se omiten porque esta acción
     * no tiene forma de pedir ancho/largo para varios items a la vez — esos
     * se agregan a mano con el modal individual.
     */
    public function agregarGrupoCompleto(string $nombreSubcategoria): void
    {
        $grupo = $this->catalogoSenaleticaAgrupado()->get($nombreSubcategoria, collect());

        $agregados = 0;
        $omitidos = 0;

        foreach ($grupo as $material) {
            if (isset($this->carrito[$material->id])) {
                continue;
            }

            if ($this->materialRequiereMedidaPedido($material)) {
                $omitidos++;

                continue;
            }

            $this->carrito[$material->id] = [
                'material_id' => $material->id,
                'cantidad' => 1,
                'ancho_pedido' => null,
                'largo_pedido' => null,
                'es_sugerido' => false,
            ];

            $agregados++;
        }

        if ($agregados === 0 && $omitidos === 0) {
            Notification::make()->warning()->title('Ya tenías todo este grupo en el pedido')->send();

            return;
        }

        Notification::make()
            ->success()
            ->title($agregados > 0 ? "Se agregaron {$agregados} material(es) del grupo" : 'No se agregó ningún material nuevo')
            ->body($omitidos > 0 ? "{$omitidos} sin medida fija, agrégalos a mano con su medida." : null)
            ->send();
    }

    /**
     * Edición inline de cantidad desde el panel de resumen del pedido, sin
     * pasar por el modal "¿Cuánto necesitas?".
     */
    public function actualizarCantidadCarrito(int|string $clave, mixed $valor): void
    {
        if (! isset($this->carrito[$clave])) {
            return;
        }

        $cantidad = $this->parseCantidad($valor);

        if ($cantidad === null) {
            Notification::make()->danger()->title($this->mensajeCantidadInvalida())->send();

            return;
        }

        $this->carrito[$clave]['cantidad'] = $cantidad;
    }

    /**
     * +1 / -1 del stepper del panel de pedido. Las unidades de señalética
     * son siempre enteras (un letrero, no "1.5 letreros"), así que estos
     * botones no pasan por parseCantidad(): son aritmética entera directa,
     * sin texto libre de por medio.
     */
    public function incrementarCantidadCarrito(int|string $clave): void
    {
        if (! isset($this->carrito[$clave])) {
            return;
        }

        $this->carrito[$clave]['cantidad'] = (int) $this->carrito[$clave]['cantidad'] + 1;
    }

    public function decrementarCantidadCarrito(int|string $clave): void
    {
        if (! isset($this->carrito[$clave])) {
            return;
        }

        $this->carrito[$clave]['cantidad'] = max(1, (int) $this->carrito[$clave]['cantidad'] - 1);
    }

    /**
     * En modo señalética las unidades son enteras (un letrero se pide
     * entero, no "1.85 letreros"); en modo material se permiten decimales
     * porque hay unidades reales que los necesitan (metros, litros, etc.).
     * Devuelve null si el texto no es un número válido para el modo actual.
     */
    protected function parseCantidad(mixed $valor): ?float
    {
        $texto = trim(str_replace(',', '.', (string) $valor));

        if ($texto === '' || ! is_numeric($texto)) {
            return null;
        }

        $numero = (float) $texto;

        if ($numero <= 0) {
            return null;
        }

        if ($this->modoFlujo === 'señaletica' && $numero != (int) $numero) {
            return null;
        }

        return $numero;
    }

    /**
     * A diferencia de parseCantidad(), una medida (ancho/largo en metros)
     * siempre admite decimales, incluso en modo señalética — un letrero
     * se pide en unidades enteras, pero puede medir 1.44m.
     */
    protected function parseMedida(mixed $valor): ?float
    {
        $texto = trim(str_replace(',', '.', (string) $valor));

        if ($texto === '' || ! is_numeric($texto)) {
            return null;
        }

        $numero = (float) $texto;

        return $numero > 0 ? $numero : null;
    }

    protected function mensajeCantidadInvalida(): string
    {
        return $this->modoFlujo === 'señaletica'
            ? 'Ingresa una cantidad entera válida (mínimo 1)'
            : 'Ingresa una cantidad válida';
    }

    public function elegirMaterial(int $materialId): void
    {
        $this->materialParaCantidadId = $materialId;
        $this->cantidadTexto = isset($this->carrito[$materialId]) ? (string) $this->carrito[$materialId]['cantidad'] : null;
        $this->anchoPedidoTexto = isset($this->carrito[$materialId]['ancho_pedido']) ? (string) $this->carrito[$materialId]['ancho_pedido'] : null;
        $this->largoPedidoTexto = isset($this->carrito[$materialId]['largo_pedido']) ? (string) $this->carrito[$materialId]['largo_pedido'] : null;
    }

    public function cancelarCantidad(): void
    {
        $this->materialParaCantidadId = null;
        $this->cantidadTexto = null;
        $this->anchoPedidoTexto = null;
        $this->largoPedidoTexto = null;
    }

    /**
     * true si el material no tiene ancho/largo fijos en el catálogo (ej.
     * "Letreros Adicionales") y por lo tanto el usuario debe especificar
     * la medida al pedirlo.
     */
    public function materialRequiereMedidaPedido(?Material $material): bool
    {
        return $material !== null && $material->dimensiones() === null;
    }

    public function confirmarCantidad(): void
    {
        if (! $this->materialParaCantidadId) {
            return;
        }

        $cantidad = $this->parseCantidad($this->cantidadTexto);

        if ($cantidad === null) {
            Notification::make()->danger()->title($this->mensajeCantidadInvalida())->send();

            return;
        }

        $material = Material::find($this->materialParaCantidadId);
        $anchoPedido = null;
        $largoPedido = null;

        if ($this->materialRequiereMedidaPedido($material)) {
            $anchoPedido = (float) str_replace(',', '.', (string) $this->anchoPedidoTexto);
            $largoPedido = (float) str_replace(',', '.', (string) $this->largoPedidoTexto);

            if ($anchoPedido <= 0 || $largoPedido <= 0) {
                Notification::make()->danger()->title('Ingresa ancho y largo para este material')->send();

                return;
            }
        }

        $this->carrito[$this->materialParaCantidadId] = [
            'material_id' => $this->materialParaCantidadId,
            'cantidad' => $cantidad,
            'ancho_pedido' => $anchoPedido,
            'largo_pedido' => $largoPedido,
            'es_sugerido' => $this->carrito[$this->materialParaCantidadId]['es_sugerido'] ?? false,
        ];

        $this->materialParaCantidadId = null;
        $this->cantidadTexto = null;
        $this->anchoPedidoTexto = null;
        $this->largoPedidoTexto = null;
    }

    /**
     * Opciones de tipo para un item manual, acotadas por $modoFlujo: en
     * modo señalética no tiene sentido preguntar (ya se sabe), en modo
     * material el usuario elige entre material/seguridad.
     *
     * @return array<string, string>
     */
    public function tiposManualDisponibles(): array
    {
        if ($this->modoFlujo === 'señaletica') {
            return ['señaletica' => 'Señalética'];
        }

        return ['material' => 'Material', 'seguridad' => 'Seguridad'];
    }

    public function abrirModalManual(): void
    {
        $opciones = $this->tiposManualDisponibles();

        $this->modalManualAbierto = true;
        $this->descripcionManual = null;
        $this->anchoManualTexto = null;
        $this->largoManualTexto = null;
        $this->fotoReferenciaManual = null;
        $this->tipoManualSeleccionado = count($opciones) === 1 ? array_key_first($opciones) : null;
        $this->cantidadManualTexto = null;
        $this->errorAnchoManual = null;
        $this->errorLargoManual = null;
    }

    public function cancelarManual(): void
    {
        $this->modalManualAbierto = false;
        $this->errorAnchoManual = null;
        $this->errorLargoManual = null;
    }

    public function seleccionarTipoManual(string $tipo): void
    {
        $this->tipoManualSeleccionado = $tipo;
    }

    public function confirmarManual(): void
    {
        if (blank($this->descripcionManual) || blank($this->tipoManualSeleccionado)) {
            Notification::make()->danger()->title('Completa la descripción y el tipo de pedido')->send();

            return;
        }

        // Item sin registro en el catálogo: la medida no queda guardada en
        // ningún otro lado, así que ancho y largo son obligatorios (a
        // diferencia del ancho/largo del catálogo, que puede quedar vacío
        // porque el material ya tiene su propia medida fija).
        $ancho = $this->parseMedida($this->anchoManualTexto);
        $largo = $this->parseMedida($this->largoManualTexto);

        $this->errorAnchoManual = $ancho === null ? 'Ingresa el ancho del letrero' : null;
        $this->errorLargoManual = $largo === null ? 'Ingresa el largo del letrero' : null;

        if ($ancho === null || $largo === null) {
            return;
        }

        $cantidad = (float) str_replace(',', '.', (string) $this->cantidadManualTexto);

        if ($cantidad <= 0) {
            Notification::make()->danger()->title('Ingresa una cantidad válida')->send();

            return;
        }

        $rutaFoto = $this->fotoReferenciaManual?->store('requerimientos-referencias', 'public');

        $this->carrito['manual-'.Str::uuid()] = [
            'material_id' => null,
            'descripcion_manual' => $this->descripcionManual,
            'ancho_pedido' => $ancho,
            'largo_pedido' => $largo,
            'foto_referencia' => $rutaFoto,
            'cantidad' => $cantidad,
            'es_sugerido' => false,
            'tipo' => $this->tipoManualSeleccionado,
        ];

        $this->modalManualAbierto = false;

        Notification::make()->success()->title('Agregado al pedido')->send();
    }

    public function quitarDelCarrito(int|string $clave): void
    {
        unset($this->carrito[$clave]);
    }

    public function carritoDecorado(): Collection
    {
        if (empty($this->carrito)) {
            return collect();
        }

        $idsCatalogo = collect($this->carrito)->pluck('material_id')->filter()->values();
        $materiales = Material::whereIn('id', $idsCatalogo)->with('subcategoria')->get()->keyBy('id');

        return collect($this->carrito)
            ->map(function (array $datos, $clave) use ($materiales) {
                $material = filled($datos['material_id'] ?? null) ? $materiales->get($datos['material_id']) : null;

                $dimensionesPedido = (filled($datos['ancho_pedido'] ?? null) && filled($datos['largo_pedido'] ?? null))
                    ? number_format((float) $datos['ancho_pedido'], 2).' x '.number_format((float) $datos['largo_pedido'], 2).' m'
                    : null;

                return (object) [
                    'clave' => $clave,
                    'materialId' => $datos['material_id'] ?? null,
                    'material' => $material,
                    'esCatalogado' => $material !== null,
                    'nombre' => $material?->nombre ?? ($datos['descripcion_manual'] ?? '—'),
                    'unidad' => $material?->unidad_medida,
                    'medidas' => $datos['medidas'] ?? null,
                    'dimensiones' => $material?->dimensiones() ?? $dimensionesPedido,
                    'cantidad' => $datos['cantidad'],
                    'es_sugerido' => $datos['es_sugerido'] ?? false,
                    // mismo agrupamiento que el catálogo (ver catalogoSenaleticaAgrupado)
                    'grupo' => $material ? ($material->subcategoria?->nombre ?? 'General') : 'Sin catalogar',
                ];
            })
            ->values();
    }

    /**
     * Items del carrito agrupados por subcategoría, con el mismo criterio
     * que el catálogo (catalogoSenaleticaAgrupado), para que el panel del
     * pedido se lea igual que el catálogo del que salieron.
     *
     * @return Collection<string, Collection<int, object>>
     */
    public function carritoAgrupado(): Collection
    {
        return $this->carritoDecorado()
            ->groupBy('grupo')
            ->sortKeys();
    }

    /**
     * Abre la previsualización final del pedido (Obra, adicional de si
     * aplica, lista completa de items). No crea nada en base de datos
     * todavía — eso solo pasa al confirmar desde ahí (ver enviar()).
     */
    public function revisarPedido(): void
    {
        if (empty($this->carrito)) {
            Notification::make()->danger()->title('Agrega al menos un material')->send();

            return;
        }

        $this->revisandoPedido = true;
    }

    /**
     * Vuelve del preview al catálogo sin tocar el carrito, para que el
     * usuario pueda seguir editando lo ya agregado.
     */
    public function volverAEditarPedido(): void
    {
        $this->revisandoPedido = false;
    }

    public function requerimientoOriginalSeleccionado(): ?Requerimiento
    {
        return $this->requerimientoOriginalId ? Requerimiento::find($this->requerimientoOriginalId) : null;
    }

    public function enviar(): void
    {
        $user = auth()->user();

        if (! $this->obraId || ! PermisoRequerimiento::puedeCrear($user, $this->obraId)) {
            abort(403);
        }

        if (empty($this->carrito)) {
            Notification::make()->danger()->title('Agrega al menos un material')->send();

            return;
        }

        $idsCatalogo = collect($this->carrito)->pluck('material_id')->filter()->values();
        $materiales = Material::whereIn('id', $idsCatalogo)->get()->keyBy('id');

        $tipos = collect($this->carrito)
            ->map(function (array $datos) use ($materiales) {
                if (filled($datos['material_id'] ?? null)) {
                    return TipoRequerimiento::desdeCategoriaNombre($materiales->get($datos['material_id'])?->categoriaEfectiva()?->nombre);
                }

                return TipoRequerimiento::from($datos['tipo']);
            })
            ->unique(fn (TipoRequerimiento $t) => $t->value);

        if ($tipos->count() > 1) {
            Notification::make()
                ->danger()
                ->title('Este pedido mezcla tipos distintos')
                ->body('Separa el pedido por tipo (material / seguridad / señalética) y envíalos por separado.')
                ->send();

            return;
        }

        $tipo = $tipos->first();

        // Si jefe_proyectos/jefe_ssoma/administrador crean un pedido de
        // señalética directamente, ya coordinaron la necesidad por fuera
        // del sistema: nace aprobado, se salta el paso de aprobación.
        $autoAprobado = $tipo === TipoRequerimiento::Señaletica
            && $user->hasAnyRole(['administrador', 'jefe_proyectos', 'jefe_ssoma']);

        DB::transaction(function () use ($user, $tipo, $autoAprobado) {
            $requerimiento = Requerimiento::create([
                'obra_id' => $this->obraId,
                'requerimiento_original_id' => $this->requerimientoOriginalId,
                'solicitado_por' => $user->id,
                'tipo' => $tipo->value,
                'estado' => $autoAprobado ? EstadoRequerimiento::Aprobado->value : EstadoRequerimiento::Pendiente->value,
                'fecha_solicitud' => now(),
                'aprobado_por' => $autoAprobado ? $user->id : null,
                'fecha_aprobacion' => $autoAprobado ? now() : null,
            ]);

            foreach ($this->carrito as $datos) {
                $requerimiento->items()->create([
                    'material_id' => $datos['material_id'] ?? null,
                    'descripcion_manual' => $datos['descripcion_manual'] ?? null,
                    'medidas' => $datos['medidas'] ?? null,
                    'ancho_pedido' => $datos['ancho_pedido'] ?? null,
                    'largo_pedido' => $datos['largo_pedido'] ?? null,
                    'foto_referencia' => $datos['foto_referencia'] ?? null,
                    'cantidad' => $datos['cantidad'],
                    'es_sugerido' => $datos['es_sugerido'] ?? false,
                ]);
            }
        });

        Notification::make()->success()->title('Pedido enviado')->body($autoAprobado ? 'Ya quedó aprobado.' : 'Queda pendiente de aprobación.')->send();

        $this->redirect(ObraResource::getUrl('checklist-ejecutar', ['record' => $this->obraId]));
    }
}
