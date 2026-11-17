// Submit small forms (sort order, basket quantities, order status) as soon as they change.
document.querySelectorAll('[data-autosubmit]').forEach((el) => {
  el.addEventListener('change', () => el.form && el.form.requestSubmit());
});
