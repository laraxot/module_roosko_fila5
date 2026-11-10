<?php

declare(strict_types=1);

return [
    'home' => 'Torna alla pagina di accesso',
    '403' => ['title' => 'Accesso non consentito', 'message' => 'Il tuo utente non ha il permesso per questa pagina. Se ti serve, chiedilo all\'amministratore dell\'impianto.'],
    '404' => ['title' => 'Pagina non trovata', 'message' => 'L\'indirizzo non esiste o il record non e\' visibile con il tuo utente.'],
    '419' => ['title' => 'Sessione scaduta', 'message' => 'La pagina e\' rimasta aperta troppo a lungo. Ricaricala e ripeti l\'operazione.'],
    '429' => ['title' => 'Troppe richieste', 'message' => 'Attendi qualche secondo e riprova.'],
    '500' => ['title' => 'Errore del server', 'message' => 'Qualcosa e\' andato storto. L\'errore e\' stato registrato; se si ripete avvisa l\'amministratore indicando l\'ora.'],
    '503' => ['title' => 'Manutenzione in corso', 'message' => 'Stiamo aggiornando Roosko. Riprova tra qualche minuto.'],
];
