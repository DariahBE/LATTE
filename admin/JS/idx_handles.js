/**
 * Attaches the click event listeners to all elements in the DOM
 * that have the idxBolt class attached to them.
 */
function attach_dom_idx_triggers() {
    const elements = document.querySelectorAll('.idxBolt');

    elements.forEach(element => {
        element.addEventListener('click', function(event) {
            event.preventDefault();
            update_idx_trigger(this, event);
        });
    });
}

/**
 * Handles toggling property indexes in Neo4j via Cypher AJAX endpoint.
 * Disables the clicked button during processing and self-updates the
 * button state, text, and styles upon completion.
 *
 * @param {HTMLElement} btn - The clicked button element
 * @param {Event} [event] - The click event object
 */
function update_idx_trigger(btn, event) {
    if (!btn) {
        let e = (event && (event.currentTarget || event.target)) || window.event?.target;
        btn = e ? e.closest('.idxBolt') : null;
    }
    if (!btn || btn.disabled) {
        return;
    }

    const label = btn.getAttribute('data-node-label') || btn.getAttribute('data_nodelabel');
    const property = btn.getAttribute('data-node-prop') || btn.getAttribute('data_nodeprop');
    let idxname = btn.getAttribute('data-idxname') || btn.getAttribute('data_idxname') || '';
    const hasIdx = btn.classList.contains('hasIndex');
    const action = hasIdx ? 'drop' : 'add';

    if (!label || !property) {
        console.error('Missing label or property on index trigger element:', btn);
        return;
    }

    // Save previous state for rollback on error.
    const previousHtml = btn.innerHTML;
    const previousTitle = btn.getAttribute('title') || '';
    const previousIsIndexed = hasIdx;

    // Immediately disable the button and show spinner loading feedback.
    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');
    btn.innerHTML = `<i class="fas fa-spinner fa-spin text-[11px]"></i> <span>${action === 'add' ? 'Indexing...' : 'Dropping...'}</span>`;

    const baseUrl = (typeof window.BASE_URL === 'string') ? window.BASE_URL : '';

    fetch(`${baseUrl}/AJAX/getdisposabletoken.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Token request failed with status ${response.status}`);
            }
            return response.json();
        })
        .then(token => {
            const formData = new FormData();
            formData.append('token', token);
            formData.append('label', label);
            formData.append('prop', property);
            formData.append('action', action);
            if (idxname) {
                formData.append('idxname', idxname);
            }

            return fetch(`${baseUrl}/admin/ajax/update_index.php`, {
                method: 'POST',
                body: formData
            });
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Index update request failed with status ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result && result.added === 1) {
                // Self-update to indexed state.
                btn.classList.remove('noIndex', 'bg-rose-50', 'text-rose-700', 'hover:bg-rose-100', 'border-rose-200');
                btn.classList.add('hasIndex', 'bg-emerald-50', 'text-emerald-700', 'hover:bg-emerald-100', 'border-emerald-200');
                if (result.name) {
                    btn.setAttribute('data-idxname', result.name);
                    btn.setAttribute('data_idxname', result.name);
                }
                btn.setAttribute('title', 'Indexed: click to drop index');
                btn.innerHTML = '<i class="fas fa-bolt text-[11px]"></i> <span>Indexed</span>';
            } else if (result && result.removed === 1) {
                // Self-update to unindexed state.
                btn.classList.remove('hasIndex', 'bg-emerald-50', 'text-emerald-700', 'hover:bg-emerald-100', 'border-emerald-200');
                btn.classList.add('noIndex', 'bg-rose-50', 'text-rose-700', 'hover:bg-rose-100', 'border-rose-200');
                btn.removeAttribute('data-idxname');
                btn.removeAttribute('data_idxname');
                btn.setAttribute('title', 'Not indexed: click to create index');
                btn.innerHTML = '<i class="fas fa-bolt text-[11px]"></i> <span>No index</span>';
            } else {
                console.warn('Unexpected index update response:', result);
                btn.innerHTML = previousHtml;
                btn.setAttribute('title', previousTitle);
            }
        })
        .catch(error => {
            console.error('Error updating index:', error);
            // Revert to original content on error.
            btn.innerHTML = previousHtml;
            btn.setAttribute('title', previousTitle);
            if (previousIsIndexed) {
                btn.classList.add('hasIndex', 'bg-emerald-50', 'text-emerald-700', 'hover:bg-emerald-100', 'border-emerald-200');
                btn.classList.remove('noIndex', 'bg-rose-50', 'text-rose-700', 'hover:bg-rose-100', 'border-rose-200');
            } else {
                btn.classList.add('noIndex', 'bg-rose-50', 'text-rose-700', 'hover:bg-rose-100', 'border-rose-200');
                btn.classList.remove('hasIndex', 'bg-emerald-50', 'text-emerald-700', 'hover:bg-emerald-100', 'border-emerald-200');
            }
            alert('Failed to update index. Please check the server logs.');
        })
        .finally(() => {
            // Re-enable the button once processing is complete.
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
        });
}
