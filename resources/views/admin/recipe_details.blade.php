@php
    $ingredientes = $recipe->recetaInstruccionReceta ?? collect();
    $resultados   = $recipe->recetaResultados ?? collect();
@endphp

<div style="padding: 10px 20px;">

    <h4>Ingredientes</h4>
    @if($ingredientes->count())
        <table class="table table-sm table-bordered" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ingrediente / Subreceta</th>
                    <th>Instrucción</th>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ingredientes as $i => $rir)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            @if($rir->instruccion && $rir->instruccion->ingrediente)
                                {{ $rir->instruccion->ingrediente->nombre }}
                            @elseif($rir->subreceta)
                                <em>{{ $rir->subreceta->titulo }}</em>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $rir->instruccion->nombre ?? '—' }}</td>
                        <td>{{ optional($rir->rirm->first())->cantidad ?? '—' }}</td>
                        <td>{{ optional($rir->rirm->first())->medida->nombre ?? '—' }}</td>
                        <td>{{ $rir->nota ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">Sin ingredientes.</p>
    @endif

    <h4>Resultados / Porciones</h4>
    @if($resultados->count())
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Medida</th>
                    <th>Principal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultados as $resultado)
                    <tr>
                        <td>{{ $resultado->cantidad }}</td>
                        <td>{{ $resultado->medida->nombre ?? '—' }}</td>
                        <td>{{ $resultado->active ? 'Sí' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">Sin resultados.</p>
    @endif

</div>
