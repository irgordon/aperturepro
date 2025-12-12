jQuery(document).ready(function($){

    // --- State Management ---
    let items = []; // Array of objects { desc, qty, rate }

    // --- Selectors ---
    const $listContainer = $('#ap_items_list_visual');
    const $hiddenContainer = $('#ap_items_hidden_inputs');
    const $subtotalEl = $('#ap_summary_subtotal');
    const $totalEl = $('#ap_summary_total');

    const $nameInput = $('#ap_new_item_name');
    const $qtyInput = $('#ap_new_item_qty');
    const $priceInput = $('#ap_new_item_price');
    const $descInput = $('#ap_new_item_desc');

    // --- Initial Load (if editing existing invoice) ---
    // We need to parse existing hidden inputs if any (from server render)
    // For now, we assume the server renders the *data* into a JS array or we parse the DOM.
    // To keep it simple, we'll let the server render the hidden inputs, and on load, we rebuild the visual list.
    // However, if we replace the whole UI, we might lose the standard loop.
    // Strategy: We will look for existing data in a global variable `ap_invoice_data` if available.

    if ( typeof ap_invoice_data !== 'undefined' && ap_invoice_data.items ) {
        ap_invoice_data.items.forEach(item => addItemToDOM(item));
        updateTotals();
    }

    // --- Event Listeners ---

    // 1. Radio Toggles (Amount vs Qty vs Hours)
    $('input[name="ap_item_type_selector"]').change(function(){
        const val = $(this).val();
        if ( val === 'amount' ) {
            $qtyInput.parent().hide();
            $qtyInput.val(1);
            $priceInput.attr('placeholder', 'Amount');
        } else if ( val === 'hours' ) {
            $qtyInput.parent().show();
            $qtyInput.prev('label').text('Hours'); // Assuming label exists or placeholder
            $qtyInput.attr('placeholder', 'Hours');
            $priceInput.attr('placeholder', 'Rate per hour');
        } else {
            // Quantity
            $qtyInput.parent().show();
            $qtyInput.prev('label').text('Qty');
            $qtyInput.attr('placeholder', 'Qty');
            $priceInput.attr('placeholder', 'Price per unit');
        }
    });

    // 2. Add Item Button
    $('#ap_add_item_btn').click(function(e){
        e.preventDefault();

        const name = $nameInput.val().trim();
        const qty = parseFloat($qtyInput.val()) || 0;
        const rate = parseFloat($priceInput.val()) || 0;
        const desc = $descInput.val().trim(); // Optional description

        if ( !name ) {
            alert('Please enter a name.');
            return;
        }
        if ( qty <= 0 ) {
            alert('Quantity must be greater than 0.');
            return;
        }

        const item = {
            desc: name + (desc ? ' - ' + desc : ''), // Combine name and desc for simple storage or store separately
            qty: qty,
            rate: rate
        };

        addItemToDOM(item);
        updateTotals();

        // Reset Inputs
        $nameInput.val('');
        $qtyInput.val(1);
        $priceInput.val('');
        $descInput.val('');
        $('input[name="ap_item_type_selector"][value="quantity"]').prop('checked', true).trigger('change');
    });

    // 3. Remove Item
    $(document).on('click', '.ap-inv-remove-item', function(){
        $(this).closest('.ap-inv-item-row').remove();
        updateTotals();
    });

    // --- Functions ---

    function addItemToDOM(item) {
        const total = item.qty * item.rate;

        // 1. Visual Row
        const html = `
            <div class="ap-inv-item-row">
                <div class="ap-inv-item-details">
                    <strong>${esc(item.desc)}</strong>
                    <span class="ap-inv-item-meta">${item.qty} x $${item.rate.toFixed(2)}</span>
                </div>
                <div class="ap-inv-right">
                    <span class="ap-inv-item-total">$${total.toFixed(2)}</span>
                    <span class="dashicons dashicons-trash ap-inv-remove-item"></span>
                </div>
                <!-- Hidden Inputs nested here or separately -->
                <input type="hidden" name="ap_item_desc[]" value="${esc(item.desc)}">
                <input type="hidden" name="ap_item_qty[]" value="${item.qty}">
                <input type="hidden" name="ap_item_rate[]" value="${item.rate}">
            </div>
        `;

        $listContainer.append(html);
    }

    function updateTotals() {
        let subtotal = 0;

        // Iterate over visual rows (which contain the values)
        // Or simpler: iterate over the hidden inputs we just appended
        // But iterating DOM is safest to match what's there
        $('.ap-inv-item-row').each(function(){
            const q = parseFloat($(this).find('input[name="ap_item_qty[]"]').val()) || 0;
            const r = parseFloat($(this).find('input[name="ap_item_rate[]"]').val()) || 0;
            subtotal += (q * r);
        });

        $subtotalEl.text('$' + subtotal.toFixed(2));
        $totalEl.text('$' + subtotal.toFixed(2));
    }

    function esc(text) {
        return $('<div>').text(text).html();
    }

    // Trigger initial type check
    $('input[name="ap_item_type_selector"]:checked').trigger('change');

});
