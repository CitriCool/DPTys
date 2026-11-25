@php
    $etiquetas = ['vencido' => 'Vencido', 'en_revision' => 'En revisión', 'proximo' => 'Próximo a vencer', 'en_plazo' => 'En plazo', 'cumplido' => 'Cumplido'];
    $iconos = ['vencido' => 'fas fa-exclamation-circle', 'en_revision' => 'fas fa-hourglass-half', 'proximo' => 'fas fa-clock', 'en_plazo' => 'fas fa-check', 'cumplido' => 'fas fa-check-double'];
@endphp
<span class="semaforo semaforo-{{ $hito->semaforo }}"><i class="{{ $iconos[$hito->semaforo] }}" aria-hidden="true"></i>{{ $etiquetas[$hito->semaforo] }}</span>
