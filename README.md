# cartellone-plugin
Wordpress plugin for managing theatrical events

## Nascondere moduli/sezioni quando un loop Divi è vuoto

### Cos'è

Questa funzionalità permette di nascondere automaticamente moduli o sezioni Divi quando un loop associato non restituisce risultati. L'associazione avviene tramite attributi `data-` espliciti, senza dipendere da `adminLabel`.

### Come funziona

1. Il plugin aggancia `divi_loop_data_after_execution` per verificare, al volo, se ogni loop ha risultati.
2. I loop monitorati vengono identificati tramite un attributo personalizzato `data-loop-id` impostato nel modulo loop.
3. I moduli/sezioni da nascondere vengono identificati tramite `data-hide-when-loop-empty`, con lo stesso valore del loop da monitorare.
4. Quando un loop è vuoto, nel footer viene stampato uno script jQuery che nasconde tutti gli elementi con `data-hide-when-loop-empty` corrispondente.

### Configurazione in Divi Builder

**Nel modulo Loop:**
- Aggiungi un attributo personalizzato al modulo loop:
  - Nome: `data-loop-id`
  - Valore: un identificativo a tua scelta (es. `prossimamente`)

**Nel modulo/sezione da nascondere:**
- Aggiungi un attributo personalizzato alla sezione/modulo:
  - Nome: `data-hide-when-loop-empty`
  - Valore: lo stesso identificativo del loop (es. `prossimamente`)

### Esempio pratico

Per la sezione "Prossimamente" della homepage del Teatro Bibiena:

1. Nel loop `loop-g7lmus5ad6` aggiungi l'attributo personalizzato:
   - `data-loop-id="prossimamente"`
2. Nella sezione "Prossimamente" aggiungi l'attributo:
   - `data-hide-when-loop-empty="prossimamente"`

Quando non ci sono spettacoli futuri, la sezione viene nascosta automaticamente con `display: none`.

### Note

- Questa funzionalità è gestita dalla classe `Cartellone\Divi\LoopHide`.
- Lo script jQuery viene caricato automaticamente nel footer solo se esistono loop vuoti.
- L'approccio non dipende da `adminLabel` né da intestazioni (`h1`-`h6`), quindi è stabile e non fragile.
- Il collegamento tra loop e modulo avviene esclusivamente tramite l'attributo personalizzato `data-loop-id`, quindi è sotto il controllo dell'utente e non dipende da identificativi interni di Divi.

## Filtrare eventi per data nei loop Divi

### Cos'è

Questa funzionalità permette di filtrare gli eventi del cartellone all'interno di un loop Divi in base alla data dello spettacolo, senza modifiche manuali alla query.

### Come funziona

Il plugin aggancia `divi_loop_data_after_execution` e, se l'attributo personalizzato `data-loop` è presente nel modulo loop, applica un filtro sulla meta `cartellone_data_sort`.

### Attributi supportati

| Valore | Comportamento |
|--------|---------------|
| `future` | Mostra solo gli spettacoli con data **>= oggi** |
| `past` | Mostra solo gli spettacoli con data **< oggi** |
| `off` | Nessun filtro sulla data |

### Configurazione in Divi Builder

Nel modulo Loop aggiungi un attributo personalizzato:

- Nome: `data-loop`
- Valore: `future`, `past` o `off`

### Esempi

**Solo spettacoli futuri:**
- `data-loop="future"`

**Solo spettacoli passati:**
- `data-loop="past"`

**Nessun filtro:**
- `data-loop="off"`

### Note

- Il confronto avviene sulla meta `cartellone_data_sort` (timestamp UNIX).
- La data di riferimento è l'inizio della giornata corrente (`today`) nel fuso orario di WordPress.
- Se `data-loop` non è impostato, non viene applicato alcun filtro.
