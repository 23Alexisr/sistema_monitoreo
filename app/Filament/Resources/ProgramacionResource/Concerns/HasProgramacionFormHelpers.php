<?php

namespace App\Filament\Resources\ProgramacionResource\Concerns;

use App\Models\Empleado;
use App\Models\Programacion;
use Filament\Forms;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait HasProgramacionFormHelpers
{
    protected static function especialidadLabels(): array
    {
        return [
            'electricista' => 'Electricista',
            'conductor' => 'Conductor',
            'instalador' => 'Instalador',
            'pintor' => 'Pintor',
            'auxiliar' => 'Auxiliar',
        ];
    }

    protected static function empleadosActivos(): Collection
    {
        return Empleado::query()->where('estado', 'activo')->orderBy('nombre_completo')->get();
    }

    protected static function nombreCorto(string $nombreCompleto): string
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto)) ?: [$nombreCompleto];

        if (count($partes) <= 1) {
            return $nombreCompleto;
        }

        return $partes[0].' '.mb_strtoupper(mb_substr($partes[array_key_last($partes)], 0, 1)).'.';
    }

    protected static function seccionLabel(string $texto): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('label_'.Str::slug($texto, '_'))
            ->hiddenLabel()
            ->content(new HtmlString(
                '<p style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-secondary,#9ca3af);">'
                .e($texto).'</p>'
            ))
            ->columnSpanFull();
    }

    protected function conflictosParaMostrar(?array $empleadoIds, ?string $fecha, ?int $excluirObraId = null): HtmlString
    {
        if (empty($empleadoIds) || ! $fecha) {
            return new HtmlString('');
        }

        $lineas = [];

        foreach (Empleado::query()->whereIn('id', $empleadoIds)->get() as $empleado) {
            $registros = Programacion::otrasAsignacionesEseDia($empleado->id, $fecha, $excluirObraId);

            if ($registros->isEmpty()) {
                continue;
            }

            $detalle = $registros->map(function (Programacion $registro) {
                $partes = [$registro->obra?->nombre ?? 'obra eliminada'];

                if ($registro->hora) {
                    $partes[] = 'a las '.$registro->hora->format('h:i A');
                }

                if ($registro->orden) {
                    $partes[] = '(parada '.$registro->orden.')';
                }

                return implode(' ', $partes);
            })->implode(', ');

            $lineas[] = '<strong>'.e($empleado->nombre_completo).'</strong> ya está programado en: '.e($detalle);
        }

        if (empty($lineas)) {
            return new HtmlString('');
        }

        return new HtmlString(
            '<div style="display:flex; gap:8px; align-items:flex-start; border-radius:10px; background:#FFFBEB; border:1px solid #FDE68A; padding:10px 12px; font-size:12.5px; color:#92400E; line-height:1.5;">'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.25 3.75h.008v.008h-.008v-.008Z" /></svg>'
            .'<span>'.implode('<br>', $lineas).'</span>'
            .'</div>'
        );
    }

    /**
     * Advertencia (no bloqueante) cuando hay 2 o más personas con
     * especialidad de conductor entre las seleccionadas en ESTA asignación
     * (mismo formulario: mismo vehículo/obra/fecha/personal elegido), pero
     * no queda claro cuál de ellas maneja. Nunca compara contra otras
     * asignaciones ya guardadas en la base — solo mira el estado actual del
     * formulario, así que un acompañante con especialidad de conductor, o
     * varios vehículos distintos para la misma obra, no disparan nada.
     */
    protected function advertenciaConductores(?array $empleadoIds, mixed $conductorId): HtmlString
    {
        if (empty($empleadoIds)) {
            return new HtmlString('');
        }

        $conductores = Empleado::query()
            ->whereIn('id', $empleadoIds)
            ->where('especialidad', 'conductor')
            ->orderBy('nombre_completo')
            ->get();

        if ($conductores->count() < 2) {
            return new HtmlString('');
        }

        $nombres = $conductores->pluck('nombre_completo')->map(fn (string $n) => e($n))->implode(', ');
        $marcado = $conductorId ? $conductores->firstWhere('id', (int) $conductorId) : null;

        $mensaje = $marcado
            ? "Hay {$conductores->count()} personas con especialidad de conductor en esta asignación ({$nombres}), pero solo <strong>".e($marcado->nombre_completo).'</strong> quedará marcado como quien maneja hoy. ¿Es correcto?'
            : "Hay {$conductores->count()} personas con especialidad de conductor en esta asignación ({$nombres}), pero ninguna quedará marcada como quien maneja hoy. ¿Seguro que no quieres indicar cuál conduce?";

        return new HtmlString(
            '<div style="display:flex; gap:8px; align-items:flex-start; border-radius:10px; background:#F0F9FF; border:1px solid #BAE6FD; padding:10px 12px; font-size:12.5px; color:#0369A1; line-height:1.5;">'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.25 3.75h.008v.008h-.008v-.008Z" /></svg>'
            .'<span>'.$mensaje.'</span>'
            .'</div>'
        );
    }
}
