document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.querySelector(".mobile-menu-toggle");
  const mainNavs = document.querySelectorAll(".main-nav");

  if (menuToggle && mainNavs.length) {
    menuToggle.addEventListener("click", function () {
      mainNavs.forEach(function (nav) {
        nav.classList.toggle("active");
      });
    });
  }

  /* =====================================================
       PRODUCT QUANTITY
       ===================================================== */

  document.querySelectorAll(".quantity-btn").forEach(function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();

      const input = document.querySelector("#quantity");

      if (!input) {
        return;
      }

      let quantity = parseInt(input.value, 10) || 1;
      const minimum = parseInt(input.min, 10) || 1;
      const maximum = parseInt(input.max, 10) || Infinity;

      if (button.dataset.action === "increase") {
        quantity++;
      } else if (button.dataset.action === "decrease") {
        quantity--;
      }

      quantity = Math.max(minimum, Math.min(quantity, maximum));

      input.value = quantity;
    });
  });
});

/* =====================================================
       BACK TO TOP
       ===================================================== */

const backToTop = document.querySelector("#backToTop");

if (backToTop) {
  window.addEventListener("scroll", function () {
    if (window.scrollY > 400) {
      backToTop.classList.add("show");
    } else {
      backToTop.classList.remove("show");
    }
  });

  backToTop.addEventListener("click", function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });
}
