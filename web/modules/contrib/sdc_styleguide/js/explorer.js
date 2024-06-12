(($, Drupal) => {

  /**
   * Initializes the explorer interaction.
   */
  Drupal.behaviors.initializeExplorer = {
    attach: (context, settings) => {
      once('explorer-init', '.sdc-styleguide-explorer', context).forEach(ex => {
        // Initializes links to update the iframe src.
        ex.querySelectorAll('.sdc-styleguide-explorer__demo-link').forEach(a => {
          a.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            document
              .querySelector('.sdc-styleguide-viewer__iframe')
              .setAttribute('src', e.target.getAttribute('href'));
          });
        });
      });
    }
  };

})(jQuery, Drupal);
