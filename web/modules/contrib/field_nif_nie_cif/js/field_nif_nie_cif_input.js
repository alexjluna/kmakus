(function (Drupal, once) {
  Drupal.behaviors.identificationNumberInputFilter = {
    attach: function (context) {
      once('identificationNumberInputFilter', 'input[data-identification-number]', context).forEach(function (element) {
        element.addEventListener('input', function () {
          element.value = element.value.toUpperCase();
        });

        element.addEventListener('keypress', function (e) {
          const charCode = e.which ? e.which : e.keyCode;
          if (!(charCode > 47 && charCode < 58) &&
            !(charCode > 64 && charCode < 91) &&
            !(charCode > 96 && charCode < 123)) {
            e.preventDefault();
          }
        });
      });
    }
  };
})(Drupal, once);
