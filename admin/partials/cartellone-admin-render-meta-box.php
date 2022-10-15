<?php
/**
* Renders plugin meta box.
*
* @link       http://photomarketing.it
* @since      1.0.0
*
* @author     Massimiliano Masserelli <info@photomarketing.it>
*/
wp_nonce_field('_cartellone_nonce', 'cartellone_nonce');
$ev = $evdata->getData();
?>
<table>
  <tr>
    <td>
      <label for="cartellone_data"><?php _e("Data", "cartellone"); ?></label>
    </td>
    <td>
      <input type="text" name="cartellone_data" class="cartellone_data" value="<?php echo date('d/m/Y', $ev['data']); ?>" size="12">
      <label for="cartellone_ora"><?php _e("Ore", "cartellone"); ?></label>
      <input type="text" name="cartellone_ora" class="cartellone_ora" value="<?php echo $ev['ora']; ?>" size="12">
    </td>
  </tr>
  <tr>
    <td>
      <label for="cartellone_produzione"><?php _e("Produzione", "cartellone"); ?></label>
    </td>
    <td>
      <input type="text" name="cartellone_produzione" value="<?php echo $ev['produzione']; ?>">
    </td>
  </tr>
  <tr>
    <td>
      <label for="cartellone_protagonisti"><?php _e("Protagonisti", "cartellone"); ?></label>
    </td>
    <td>
      <input type="text" name="cartellone_protagonisti" value="<?php echo $ev['protagonisti']; ?>">
    </td>
  </tr>
  <tr>
    <td>
      <label for="cartellone_credits"><?php _e("Credits", "cartellone"); ?></label>
    </td>
    <td>
      <textarea name="cartellone_credits" rows="3" cols="50"><?php echo $ev['credits']; ?></textarea>
    </td>
  </tr>
  <tr>
    <td>
      <label for="cartellone_vivaticket"><?php _e("VivaTicket link", "cartellone"); ?></label>
    </td>
    <td>
      <input type="text" name="cartellone_vivaticket" value="<?php echo $ev['vivaticket']; ?>">
    </td>
  </tr>
</table>

<script>
jQuery(function ($) {
  var tpdata = {
    showPeriodLabels: false,
    hours: {
      starts: 7, // First displayed hour
      ends: 22                  // Last displayed hour
    },
    minutes: {
      starts: 0, // First displayed minute
      ends: 45, // Last displayed minute
      interval: 15, // Interval of displayed minutes
      manual: []                // Optional extra entries for minutes
    },
    hourText: 'Ore', // Define the locale text for "Hours"
    minuteText: 'Minuti', // Define the locale text for "Minute"
    amPmText: ['AM', 'PM'], // Define the locale text for periods
  };

  $('.cartellone_ora').timepicker(tpdata);

  /* Italian initialisation for the jQuery UI date picker plugin. */
  /* Written by Antonello Pasella (antonello.pasella@gmail.com). */
  $.datepicker.regional['it'] = {
    closeText: "Chiudi",
    prevText: "&#x3C;Prec",
    nextText: "Succ&#x3E;",
    currentText: "Oggi",
    monthNames: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
    "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
    monthNamesShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu",
    "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
    dayNames: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"],
    dayNamesShort: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
    dayNamesMin: ["Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa"],
    weekHeader: "Sm",
    dateFormat: "dd/mm/yy",
    firstDay: 1,
    isRTL: false,
    showMonthAfterYear: false,
    yearSuffix: ""};
    $.datepicker.setDefaults($.datepicker.regional['it']);
  });

  // Imposta datepicker e timepicker per i campi relativi
  jQuery(document).ready(function () {
    jQuery(".cartellone_data").datepicker({minDate: 1, maxDate: "+2Y", dateFormat: "dd/mm/yy", regional: "it",
    // onSelect: function () {
    //   //- get date from another datepicker without language dependencies
    //   var minDate = jQuery('.iomn_op_data').datepicker('getDate');
    //   minDate.setDate(minDate.getDate() - 1);
    //   jQuery(".iomn_pre_data").datepicker("change", {maxDate: minDate});
    // }
  });

});
</script>
