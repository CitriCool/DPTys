@php
    $etiquetas = ['vencido' => 'Vencido', 'proximo' => 'Próximo a vencer', 'en_plazo' => 'En plazo', 'cumplido' => 'Cumplido'];
@endphp
<span class="semaforo semaforo-{{ $hito->semaforo }}">{{ $etiquetas[$hito->semaforo] }}</span>
