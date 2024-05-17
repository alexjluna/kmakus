(function (Drupal, once) {
  Drupal.behaviors.redsysButtonBehavior = {
    attach: function (context, settings) {
      once('emailInputFilter', 'input#email-input', context).forEach(function (element) {
        element.addEventListener('input', function () {
          element.value = element.value.toLowerCase().trim();
        });
      });
      once('descriptionInputFilter', 'textarea#description-input', context).forEach(function (element) {
        element.addEventListener('input', function () {
          let tempDiv = document.createElement('div');
          tempDiv.innerHTML = element.value;
          element.value = tempDiv.textContent || tempDiv.innerText || "";
        });
      });
    }
  };
})(Drupal, once);
