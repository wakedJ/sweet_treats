document.getElementById('categoryFilter').addEventListener('change', function() {
    var category = this.value;
    var sort = document.getElementById('priceSort').value;
    window.location.href = 'shop.php?category=' + category + '&sort=' + sort;
});

document.getElementById('priceSort').addEventListener('change', function() {
    var sort = this.value;
    var category = document.getElementById('categoryFilter').value;
    window.location.href = 'shop.php?category=' + category + '&sort=' + sort;
});
