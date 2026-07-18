# cartellone-plugin
Wordpress plugin for managing theatrical events

## Nascondere moduli/sezioni quando un loop Divi è vuoto

### Cos'è

Questa funzionalità permette di nascondere automaticamente moduli o sezioni Divi in base allo stato di un loop associato (vuoto, paginabile o interamente visibile in pagina). L'associazione avviene tramite attributi `data-` espliciti, senza dipendere da `adminLabel`.

### Come funziona

1. Il plugin aggancia `divi_loop_data_after_execution` per verificare, al volo, lo stato di ogni loop.
2. I loop monitorati vengono identificati tramite un attributo personalizzato `data-loop-id` impostato nel modulo loop.
3. I moduli/sezioni da nascondere vengono identificati tramite uno dei seguenti attributi, con lo stesso valore del loop da monitorare:
   - `data-hide-when-loop-empty`: nasconde quando il loop è vuoto.
   - `data-hide-when-loop-paginates`: nasconde quando il loop ha più elementi di quelli mostrati in pagina (ovvero paginerebbe).
   - `data-hide-when-loop-does-not-paginate`: nasconde quando il loop entra interamente nella pagina (ovvero NON paginerebbe).
4. Nel footer viene stampato uno script jQuery che nasconde gli elementi corrispondenti in base allo stato rilevato.

### Attributi supportati

| Attributo | Nasconde quando il loop… |
|-----------|--------------------------|
| `data-hide-when-loop-empty` | non restituisce risultati |
| `data-hide-when-loop-paginates` | ha più elementi di quelli mostrati (paginerebbe) |
| `data-hide-when-loop-does-not-paginate` | entra tutto in pagina (non paginerebbe) |

Lo stato "paginabile" si determina confrontando il numero totale di risultati (`found_posts`) con gli elementi mostrati (`posts_per_page` del loop, oppure l'opzione `posts_per_page` del sito se non impostata).

### Configurazione in Divi Builder

**Nel modulo Loop:**
- Aggiungi un attributo personalizzato al modulo loop:
  - Nome: `data-loop-id`
  - Valore: un identificativo a tua scelta (es. `prossimamente`)

**Nel modulo/sezione da nascondere:**
- Aggiungi un attributo personalizzato alla sezione/modulo tra quelli supportati, con lo stesso valore del loop:
  - `data-hide-when-loop-empty="prossimamente"`
  - `data-hide-when-loop-paginates="prossimamente"`
  - `data-hide-when-loop-does-not-paginate="prossimamente"`

### Esempio pratico

Per la sezione "Prossimamente" della homepage del Teatro Bibiena:

1. Nel modulo loop aggiungi l'attributo personalizzato:
   - `data-loop-id="prossimamente"`
2. Nella sezione "Prossimamente" aggiungi l'attributo:
   - `data-hide-when-loop-empty="prossimamente"`

Entrambi gli attributi usano lo stesso valore (`prossimamente`), cioè il `data-loop-id` del loop di riferimento: il collegamento NON avviene tramite l'identificativo interno di Divi (`loopId`, es. `loop-g7lmus5ad6`), ma esclusivamente tramite il `data-loop-id` scelto da te.

Quando non ci sono spettacoli futuri, la sezione viene nascosta automaticamente con `display: none`.

### Note

- Questa funzionalità è gestita dalla classe `Cartellone\Divi\LoopHide`.
- Lo script jQuery viene caricato automaticamente nel footer solo se esiste almeno un loop che soddisfa una delle condizioni di hiding.
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
