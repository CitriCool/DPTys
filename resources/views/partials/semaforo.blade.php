@php
    $etiquetas = ['vencido' => 'Vencido', 'en_revision' => 'En revisión', 'proximo' => 'Próximo a vencer', 'en_plazo' => 'En plazo', 'cumplido' => 'Cumplido'];
@endphp
<span class="semaforo semaforo-{{ $hito->semaforo }}">{{ $etiquetas[$hito->semaforo] }}</span>
