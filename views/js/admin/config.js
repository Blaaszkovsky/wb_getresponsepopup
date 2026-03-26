(function () {
    'use strict';

    // --- API Test Connection ---
    var testBtn = document.getElementById('wb-gr-test-api');
    var testResult = document.getElementById('wb-gr-test-api-result');

    if (testBtn) {
        testBtn.addEventListener('click', function () {
            var apiKeyInput = document.querySelector('input[name="WB_GETRESPONSEPOPUP_API_KEY"]');
            var apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';

            if (!apiKey) {
                testResult.innerHTML = '<span style="color:#c0392b;">Please enter an API key first.</span>';
                return;
            }

            testBtn.disabled = true;
            testResult.innerHTML = '<span style="color:#666;">Testing connection...</span>';

            fetch(wbGrPopupAjaxUrl + '&action=testApiConnection&api_key=' + encodeURIComponent(apiKey), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    testResult.innerHTML = '<span style="color:#27ae60;"><i class="icon-check"></i> ' + data.message + '</span>';
                } else {
                    testResult.innerHTML = '<span style="color:#c0392b;"><i class="icon-times"></i> ' + data.message + '</span>';
                }
            })
            .catch(function () {
                testResult.innerHTML = '<span style="color:#c0392b;">Connection error.</span>';
            })
            .finally(function () {
                testBtn.disabled = false;
            });
        });
    }

    // --- Product Autocomplete ---
    var searchInput = document.getElementById('wb-gr-product-search');
    var resultsBox = document.getElementById('wb-gr-product-results');
    var hiddenInput = document.getElementById('wb-gr-product-ids');
    var selectedContainer = document.getElementById('wb-gr-product-selected');

    if (!searchInput || !resultsBox || !hiddenInput || !selectedContainer) {
        return;
    }

    var debounceTimer;

    searchInput.addEventListener('input', function () {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsBox.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(function () {
            fetch(wbGrPopupAjaxUrl + '&action=searchProducts&q=' + encodeURIComponent(query), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (products) {
                renderResults(products);
            })
            .catch(function () {
                resultsBox.style.display = 'none';
            });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#wb-gr-product-search') && !e.target.closest('#wb-gr-product-results')) {
            resultsBox.style.display = 'none';
        }
    });

    function getSelectedIds() {
        var val = hiddenInput.value.trim();
        if (!val) return [];
        return val.split(',').map(function (id) { return parseInt(id, 10); }).filter(function (id) { return id > 0; });
    }

    function updateHiddenInput(ids) {
        hiddenInput.value = ids.join(',');
    }

    function renderResults(products) {
        resultsBox.innerHTML = '';
        var selectedIds = getSelectedIds();
        var filtered = products.filter(function (p) {
            return selectedIds.indexOf(p.id) === -1;
        });

        if (filtered.length === 0) {
            resultsBox.style.display = 'none';
            return;
        }

        filtered.forEach(function (product) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'wb-gr-product-result-item';
            var ref = product.reference ? ' (' + product.reference + ')' : '';
            var ean = product.ean13 ? ' EAN: ' + product.ean13 : '';
            item.innerHTML = '<strong>' + escapeHtml(product.name) + '</strong>' + escapeHtml(ref) + ' <small class="text-muted">#' + product.id + escapeHtml(ean) + '</small>';

            item.addEventListener('click', function () {
                addProduct(product);
                resultsBox.style.display = 'none';
                searchInput.value = '';
            });

            resultsBox.appendChild(item);
        });

        resultsBox.style.display = 'block';
    }

    function addProduct(product) {
        var ids = getSelectedIds();
        if (ids.indexOf(product.id) !== -1) return;

        ids.push(product.id);
        updateHiddenInput(ids);

        var ref = product.reference ? ' (' + escapeHtml(product.reference) + ')' : '';
        var item = document.createElement('div');
        item.className = 'wb-gr-product-item';
        item.setAttribute('data-id', product.id);
        item.innerHTML = '<span>' + escapeHtml(product.name) + ref + ' <small class="text-muted">#' + product.id + '</small></span>'
            + ' <button type="button" class="btn btn-xs btn-danger wb-gr-product-remove">&times;</button>';

        selectedContainer.appendChild(item);
    }

    selectedContainer.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.wb-gr-product-remove');
        if (!removeBtn) return;

        var item = removeBtn.closest('.wb-gr-product-item');
        var id = parseInt(item.getAttribute('data-id'), 10);
        item.remove();

        var ids = getSelectedIds().filter(function (i) { return i !== id; });
        updateHiddenInput(ids);
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
