document.addEventListener("DOMContentLoaded", function () {
  const categoryList = document.getElementById("categoryList");
  const scrollLeftBtn = document.getElementById("scrollLeftBtn");
  const scrollRightBtn = document.getElementById("scrollRightBtn");

  if (categoryList && scrollLeftBtn && scrollRightBtn) {
    scrollLeftBtn.addEventListener("click", function () {
      categoryList.scrollBy({
        left: -220,
        behavior: "smooth",
      });
    });

    scrollRightBtn.addEventListener("click", function () {
      categoryList.scrollBy({
        left: 220,
        behavior: "smooth",
      });
    });
  }
});
