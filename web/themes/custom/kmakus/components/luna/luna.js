((Drupal) => {
  Drupal.behaviors.luna = {
    attach(context) {
      console.log('Drupal behavior attached');
      context.querySelectorAll('.luna--dismissable').forEach((luna) => {
        luna.addEventListener('click', () => {
          luna.classList.toggle('luna--dismissed');
        })
      });
    },
  };
})(Drupal);
