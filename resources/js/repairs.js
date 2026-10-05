import {appendPatternDot, normalizePattern} from './repair-pattern.js';
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
async function json(url, options = {}) {
    const response = await fetch(url, {...options, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), ...options.headers}});
    const body = await response.json();
    if (!response.ok) throw new Error(Object.values(body.errors || {}).flat().join(' ') || body.message || 'Unable to save. Try again.');
    return body;
}
const fail = error => Swal.fire({icon: 'error', text: error.message || 'Something went wrong.'});
function initRepairs() {
    document.querySelectorAll('[data-repair-unlock]').forEach(root => {
        if (root.dataset.ready) return; root.dataset.ready = 'true';
        const type = root.querySelector('[data-unlock-type]'), text = root.querySelector('[data-unlock-text]'), input = root.querySelector('[data-unlock-input]');
        const secret = root.querySelector('[data-unlock-secret]'), pattern = root.querySelector('[data-unlock-pattern]');
        const pad = root.querySelector('[data-pattern-pad]'), line = root.querySelector('[data-pattern-line]'), status = root.querySelector('[data-pattern-status]');
        let sequence = [], dragging = false, saved = false;
        const render = () => {
            line.setAttribute('points', sequence.map(dot => `${50 + ((dot - 1) % 3) * 75},${50 + Math.floor((dot - 1) / 3) * 75}`).join(' '));
            pad.querySelectorAll('[data-dot]').forEach(button => button.classList.toggle('selected', sequence.includes(Number(button.dataset.dot))));
            status.textContent = sequence.length ? `${sequence.length} dots selected${saved ? ' · Pattern saved' : ' · Press Save pattern'}` : 'No pattern selected';
        };
        const add = dot => { sequence = appendPatternDot(sequence, dot); saved = false; secret.value = ''; render(); };
        const refresh = () => {
            text.hidden = !['PIN', 'Password'].includes(type.value); pattern.hidden = type.value !== 'Pattern';
            input.inputMode = type.value === 'PIN' ? 'numeric' : 'text'; input.value = ''; secret.value = ''; sequence = []; saved = false; render();
        };
        type.addEventListener('change', refresh); refresh();
        input.addEventListener('input', () => { secret.value = input.value; });
        pad.addEventListener('pointerdown', event => {
            const button = event.target.closest('[data-dot]'); if (!button) return;
            event.preventDefault(); dragging = true; add(Number(button.dataset.dot)); pad.setPointerCapture(event.pointerId);
        });
        pad.addEventListener('pointermove', event => {
            if (!dragging) return;
            const rect = pad.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width * 250, y = (event.clientY - rect.top) / rect.height * 250;
            for (let dot = 1; dot <= 9; dot++) if (Math.hypot(x - (50 + ((dot - 1) % 3) * 75), y - (50 + Math.floor((dot - 1) / 3) * 75)) < 24) add(dot);
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(event => pad.addEventListener(event, () => { dragging = false; }));
        pad.querySelectorAll('[data-dot]').forEach(button => button.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); add(Number(button.dataset.dot)); }
        }));
        root.querySelector('[data-pattern-clear]').addEventListener('click', () => { sequence = []; saved = false; secret.value = ''; render(); });
        root.querySelector('[data-pattern-save]').addEventListener('click', () => {
            const value = normalizePattern(sequence); if (!value) { status.textContent = 'Select at least four distinct dots.'; return; }
            secret.value = value; saved = true; render();
        });
        root.closest('form').addEventListener('submit', event => {
            if (type.value === 'Pattern' && sequence.length && !saved) { event.preventDefault(); status.textContent = 'Save the pattern before submitting.'; pad.scrollIntoView({block:'center'}); }
        });
    });
    document.querySelectorAll('[data-custom-tests]').forEach(root => {
        if (root.dataset.ready) return; root.dataset.ready = 'true';
        root.querySelector('[data-custom-test-add]').addEventListener('click', () => {
            const input = root.querySelector('[data-custom-test-name]'), name = input.value.trim();
            if (!name || name.length > 100 || /[.\[\]<>]/.test(name)) { fail(new Error('Enter a test name up to 100 characters without dots or brackets.')); return; }
            const fieldName = `checklist[${name}]`;
            if ([...root.closest('form').querySelectorAll('select')].some(select => select.name === fieldName)) { fail(new Error('This test is already listed.')); return; }
            const label = document.createElement('label'); label.className = 'repair-field';
            const title = document.createElement('span'); title.textContent = name;
            const select = document.createElement('select'); select.name = fieldName; select.setAttribute('aria-label', name);
            ['Not tested','Working','Not working'].forEach(state => select.append(new Option(state, state)));
            select.value = root.querySelector('[data-custom-test-state]').value;
            label.append(title, select); root.querySelector('[data-custom-test-rows]').append(label); input.value = '';
        });
    });
    document.querySelectorAll('[data-repair-customer]').forEach(root => {
        if (root.dataset.ready) return; root.dataset.ready = 'true';
        const search = root.querySelector('[data-customer-search]'), id = root.querySelector('[data-customer-id]');
        const results = root.querySelector('[data-customer-results]'), selected = root.querySelector('[data-customer-selected]');
        let timer, generation = 0;
        const choose = customer => { id.value = customer.id; search.value = customer.name; selected.textContent = `${customer.phone || ''} · Customer #${customer.id}`; results.hidden = true; };
        search.addEventListener('input', () => {
            id.value = ''; selected.textContent = 'Select a customer from the results.'; clearTimeout(timer); const current = ++generation;
            timer = setTimeout(async () => {
                try {
                    const customers = await json(root.dataset.searchUrl + '?' + new URLSearchParams({q:search.value}));
                    if (current !== generation) return;
                    results.replaceChildren(); results.hidden = false;
                    customers.forEach(customer => { const button = document.createElement('button'); button.type = 'button'; button.textContent = `${customer.name} · ${customer.phone || ''} · #${customer.id}`; button.addEventListener('click', () => choose(customer)); results.append(button); });
                    if (!customers.length) { const message = document.createElement('p'); message.textContent = 'No customers found. Use Quick add customer.'; results.append(message); }
                } catch (error) { fail(error); }
            },250);
        });
        root.querySelector('[data-customer-add]').addEventListener('click', async () => {
            const fields = [['name','Customer name'],['phone','Phone number'],['email','Email (optional)'],['address','Address (optional)']];
            const container = document.createElement('div'); container.style.textAlign = 'left';
            fields.forEach(([name,label]) => { const node = document.createElement('label'); node.textContent = label; node.style.display = 'block'; const input = document.createElement('input'); input.name = name; input.className = 'swal2-input'; input.style.margin = '8px 0 15px'; input.style.width = '100%'; if (name === 'email') input.type = 'email'; node.append(input); container.append(node); });
            const result = await Swal.fire({title:'Quick add customer', html:container, showCancelButton:true, confirmButtonText:'Save customer', focusConfirm:false,
                preConfirm:async () => { const data = Object.fromEntries([...container.querySelectorAll('input')].map(input=>[input.name,input.value])); try {return await json(root.dataset.createUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});} catch(error){Swal.showValidationMessage(error.message);return false;} }});
            if (result.isConfirmed) choose(result.value);
        });
    });
    document.querySelectorAll('[data-problem-search]').forEach(input => {
        if (input.dataset.ready) return; input.dataset.ready = 'true';
        input.addEventListener('input', () => input.closest('.repair-card').querySelectorAll('[data-problem-list] label').forEach(label => { label.hidden = !label.textContent.toLowerCase().includes(input.value.toLowerCase()); }));
    });
    document.querySelectorAll('[data-repair-parts]').forEach(root => {
        if (root.dataset.ready) return; root.dataset.ready = 'true';
        const search=root.querySelector('[data-part-search]'), results=root.querySelector('[data-part-results]'), select=root.querySelector('[data-part-variant]'), price=root.querySelector('[data-part-price]'), label=root.querySelector('[data-part-selected]');
        let product, timer, generation=0;
        const describe=()=> { const variant=product?.variants.find(v=>String(v.id)===select.value); price.value=variant?.retail_price??product?.retail_price??'';label.textContent=product?`${product.name} · Available: ${variant?.stock??product.stock}`:'No part selected.'; };
        select.addEventListener('change',describe);
        search.addEventListener('input',()=>{clearTimeout(timer);const current=++generation;root.querySelector('[data-part-id]').value='';product=null;
            timer=setTimeout(async()=>{try{const products=await json(root.dataset.url+'?'+new URLSearchParams({q:search.value,store_id:root.dataset.store}));if(current!==generation)return;results.replaceChildren();results.hidden=false;products.forEach(item=>{const button=document.createElement('button');button.type='button';button.textContent=`${item.name} · ${item.sku||''} · ${item.variants.length?'Choose variant':('Stock '+item.stock)}`;button.addEventListener('click',()=>{product=item;root.querySelector('[data-part-id]').value=item.id;search.value=item.name;results.hidden=true;select.replaceChildren();select.append(new Option(item.variants.length?'Choose exact variant':'No variants',''));item.variants.forEach(v=>select.append(new Option(`${v.name} · Stock ${v.stock}`,v.id)));select.required=item.variants.length>0;describe();});results.append(button);});if(!products.length){const p=document.createElement('p');p.textContent='No matching inventory parts.';results.append(p);}}catch(error){fail(error);}},250);
        });
    });
    document.querySelectorAll('[data-repair-payment]').forEach(root=>{if(root.dataset.ready)return;root.dataset.ready='true';const select=root.querySelector('[data-payment-method]'),group=root.querySelector('[data-payment-cheque]');const update=()=>{group.hidden=select.value!=='Cheque';group.querySelectorAll('input').forEach(input=>{input.disabled=group.hidden;});};select.addEventListener('change',update);update();});
    document.querySelectorAll('[data-charge-form]').forEach(root=>{if(root.dataset.ready)return;root.dataset.ready='true';root.querySelector('[data-charge-template]').addEventListener('change',event=>{const option=event.target.selectedOptions[0];root.querySelector('[data-charge-name]').value=option.value;root.querySelector('[data-charge-amount]').value=option.dataset.amount??0;});});
    document.querySelectorAll('[data-repair-credential]').forEach(root=>{
        if(root.dataset.ready)return;root.dataset.ready='true';const reveal=root.querySelector('[data-credential-reveal]'),hide=root.querySelector('[data-credential-hide]'),value=root.querySelector('[data-credential-value]');if(!reveal)return;
        let timer;const conceal=()=>{clearTimeout(timer);value.textContent='••••••';hide.hidden=true;reveal.hidden=false;};
        reveal.addEventListener('click',async()=>{try{const data=await json(root.dataset.url,{method:'POST'});value.textContent=data.secret||'No saved credential';reveal.hidden=true;hide.hidden=false;timer=setTimeout(conceal,20000);}catch(error){fail(error);}});hide.addEventListener('click',conceal);document.addEventListener('visibilitychange',()=>{if(document.hidden)conceal();});window.addEventListener('pagehide',conceal);
    });
    document.querySelectorAll('[data-repair-confirm]').forEach(form=>{if(form.dataset.ready)return;form.dataset.ready='true';form.addEventListener('submit',async event=>{if(form.dataset.confirmed)return;event.preventDefault();const button=event.submitter;const result=await Swal.fire({icon:'question',text:form.dataset.repairConfirm,showCancelButton:true,confirmButtonText:'Continue'});if(result.isConfirmed){form.dataset.confirmed='true';form.requestSubmit(button);}});});
    document.querySelectorAll('[data-repair-photo]').forEach(link=>{if(link.dataset.ready)return;link.dataset.ready='true';link.addEventListener('click',event=>{event.preventDefault();Swal.fire({imageUrl:link.href,imageAlt:link.querySelector('img')?.alt,width:'90vw',showConfirmButton:false,showCloseButton:true});});});
    document.querySelectorAll('[data-photo-preview-input]').forEach(input=>{if(input.dataset.ready)return;input.dataset.ready='true';let urls=[];input.addEventListener('change',()=>{const target=input.closest('form').querySelector('[data-photo-previews]');urls.forEach(URL.revokeObjectURL);urls=[];target.replaceChildren();[...input.files].filter(file=>file.type.startsWith('image/')).forEach(file=>{const img=document.createElement('img');img.alt=file.name;const url=URL.createObjectURL(file);urls.push(url);img.src=url;target.append(img);});});});
}
document.addEventListener('DOMContentLoaded',initRepairs);
document.addEventListener('livewire:navigated',initRepairs);
