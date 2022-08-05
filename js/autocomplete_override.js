(function ($, Drupal) {
  // Overrides the default function extractLastTerm() found in core/misc/autocomplete.js
  // Default behavior splits text containing commas in autocomplete input fields into
  //  multiple search terms.
  // Single line addresses contain commas. When entered with commas autocomplete only returns results
  //  for the last portion of the address after a comma.
  let oldLastTerm = Drupal.autocomplete.extractLastTerm;
  Drupal.autocomplete.extractLastTerm = function (terms) {
    return oldLastTerm('"' + terms + '"');
  }
})(jQuery, Drupal);
