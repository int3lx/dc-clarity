// === CONFIGURATION ===
const EQUIPMENT_TABLE_COLUMNS = [
  {key: 'actions', label: 'Actions'},
  {key: 'id', label: 'Equipment ID'},
  {key: 'type', label: 'Equipment Type'},
  {key: 'brand', label: 'Equipment Brand'},
  {key: 'spec', label: 'Specifications'},
  {key: 'status', label: 'Equipment Status'},
  {key: 'location', label: 'Detailed Location'},
  {key: 'dc', label: 'Datacenter'},
  {key: 'supplier', label: 'Supplier'},
  {key: 'vendor', label: 'Current Vendor'},
  {key: 'sn', label: 'Serial Number'},
  {key: 'asset-tag', label: 'TM Asset Number'},
  {key: 'lifespan', label: 'Designed Lifespan'},
  {key: 'installed', label: 'Install Date'},
  {key: 'latest-pm', label: 'Last Maintenance Date'},
  {key: 'notes', label: 'Additional Notes'},
  {key: 'created-by', label: 'Created By'},
  {key: 'date-created', label: 'Date Created'},
];

// Get predefined options from global var or fallback to empty
function getPredefinedEquipmentTypes() {
  if (window.dclarityGlobal && window.dclarityGlobal.equipmentTypes) {
    return window.dclarityGlobal.equipmentTypes.map(e => ({ value: e.value, label: e.label }));
  }
  return [];
}

function getPredefinedDCLocations() {
  if (window.dclarityGlobal && window.dclarityGlobal.dcLocations) {
    return window.dclarityGlobal.dcLocations.map(d => ({ value: d.value, label: d.label }));
  }
  return [];
}

// === INITIALIZATION ===
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('equipment-table')) {
    initListPage();
  }
  if (document.getElementById('equipment-details')) {
    initDetailsPage();
  }
});

// === HELPER FUNCTIONS ===
async function fetchFromBackend(endpoint, params = {}) {
  const queryString = new URLSearchParams(params).toString();
  const url = endpoint + (queryString ? '?' + queryString : '');
  
  const res = await fetch(url);
  const json = await res.json();
  
  if (json.status !== 'success') {
    throw new Error(json.message || 'Backend error');
  }
  
  return json;
}

async function initListPage() {
  const dcSelect = document.getElementById('dc_location');
  const typeSelect = document.getElementById('dcfm_equipment_type');

  // Use predefined options, not from JSON
  const predefinedDCs = getPredefinedDCLocations();
  const predefinedTypes = getPredefinedEquipmentTypes();

  // Only populate if the options haven't already been added by dcclarity-global-var.js
  if (dcSelect.options.length <= 1) {
    predefinedDCs.forEach(({ value, label }) => {
      const o = document.createElement('option');
      o.value = value;
      o.textContent = label;
      dcSelect.appendChild(o);
    });
  }

  if (typeSelect.options.length <= 1) {
    predefinedTypes.forEach(({ value, label }) => {
      const o = document.createElement('option');
      o.value = value;
      o.textContent = label;
      typeSelect.appendChild(o);
    });
  }

  document.getElementById('load_list_btn').addEventListener('click', async (ev) => {
    ev.preventDefault();
    if (!confirm('Are you sure you want to load the list?')) return;
    
    try {
      const dc = dcSelect.value;
      const type = typeSelect.value;
      
      // Fetch from backend
      const response = await fetchFromBackend('backend-equipment-list/submit.php', {
        dc: dc,
        type: type
      });
      
      renderEquipmentTable(response.data);
    } catch (error) {
      console.error('Error loading equipment list:', error);
      alert('Failed to load equipment list: ' + error.message);
    }
  });
}

function renderEquipmentTable(data) {
  const table = document.getElementById('equipment-table');
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  thead.innerHTML = '';
  tbody.innerHTML = '';

  // Use configurable columns
  const cols = EQUIPMENT_TABLE_COLUMNS;

  // header row
  const hr = document.createElement('tr');
  cols.forEach(c => {
    const th = document.createElement('th');
    th.textContent = c.label;
    hr.appendChild(th);
  });
  thead.appendChild(hr);

  // filter inputs row (except Actions)
  const fr = document.createElement('tr');
  cols.forEach((c, idx) => {
    const th = document.createElement('th');
    if (c.key !== 'actions') {
      const input = document.createElement('input');
      input.type = 'search';
      input.placeholder = 'filter';
      input.dataset.col = c.key;
      input.addEventListener('input', () => applyColumnFilters(table));
      th.appendChild(input);
    }
    fr.appendChild(th);
  });
  thead.appendChild(fr);

  // Data already filtered by backend, so render all
  if (!data || data.length === 0) {
    applyColumnFilters(table);
    return;
  }

  data.forEach(item => {
    const tr = document.createElement('tr');
    tr.style.verticalAlign = 'top';

    // Actions cell
    const actionTd = document.createElement('td');
    const viewBtn = document.createElement('button');
    viewBtn.type = 'button';
    viewBtn.textContent = 'View';
    viewBtn.style.height = '20px';
    viewBtn.addEventListener('click', () => {
      const id = item.id;
      window.location.href = `dcfm-equipment-details.html?id=${encodeURIComponent(id)}`;
    });
    actionTd.appendChild(viewBtn);
    tr.appendChild(actionTd);

    // other columns based on configured keys
    const rowKeys = EQUIPMENT_TABLE_COLUMNS
      .filter(c => c.key !== 'actions')
      .map(c => c.key);

    rowKeys.forEach(k => {
      const td = document.createElement('td');
      td.textContent = item[k] ?? '';
      tr.appendChild(td);
    });

    tbody.appendChild(tr);
  });

  applyColumnFilters(table);
}

function applyColumnFilters(table) {
  const inputs = Array.from(table.querySelectorAll('thead input[data-col]'));
  const rows = Array.from(table.querySelectorAll('tbody tr'));
  let visibleCount = 0;

  rows.forEach(r => {
    let show = true;
    inputs.forEach(inp => {
      const col = inp.dataset.col;
      const colIndex = getColumnIndexByKey(col);
      const cell = r.cells[colIndex];
      const val = (cell && cell.textContent) ? cell.textContent.toLowerCase() : '';
      const q = inp.value.trim().toLowerCase();
      if (q && !val.includes(q)) show = false;
    });
    r.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });

  // Update stats
  const statsEl = document.getElementById('equipment-list-stats');
  if (statsEl) {
    if (visibleCount === 0) {
      statsEl.value = 'No match found';
    } else {
      statsEl.value = `Found ${visibleCount} match${visibleCount !== 1 ? 'es' : ''}`;
    }
  }
}

function getColumnIndexByKey(key) {
  const colIndex = EQUIPMENT_TABLE_COLUMNS.findIndex(c => c.key === key);
  return colIndex !== -1 ? colIndex : 0;
}

// === DETAILS PAGE ===
async function initDetailsPage() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');

  // populate selects for type and dc with predefined options
  const dcSelect = document.getElementById('dc_location');
  const typeSelect = document.getElementById('dcfm_equipment_type');
  
  if (dcSelect && typeSelect) {
    const predefinedDCs = getPredefinedDCLocations();
    const predefinedTypes = getPredefinedEquipmentTypes();
    if (dcSelect.options.length <= 1) {
      predefinedDCs.forEach(({ value, label }) => {
        const o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        dcSelect.appendChild(o);
      });
    }
    if (typeSelect.options.length <= 1) {
      predefinedTypes.forEach(({ value, label }) => {
        const o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        typeSelect.appendChild(o);
      });
    }
  }

  if (!id) return;

  try {
    // Fetch equipment details and history from backend
    const response = await fetchFromBackend('backend-equipment-details/submit.php', { id: id });
    
    const item = response.equipment;
    const history = response.history;

    // helper to set value safely
    const setVal = (idName, value) => {
      const el = document.getElementById(idName);
      if (!el) return;
      if (el.tagName === 'SELECT') {
        el.value = value ?? '';
      } else if (el.type === 'date') {
        if (!value) { el.value = ''; return; }
        // input expects yyyy-mm-dd; source is dd/mm/yyyy
        const parts = value.split('/');
        if (parts.length === 3) {
          const [d,m,y] = parts;
          el.value = `${y.padStart(4,'0')}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`;
        } else {
          el.value = value;
        }
      } else {
        el.value = value ?? '';
      }
    };

    setVal('dcfm_equipment_id', item.id);
    setVal('dcfm_equipment_type', item.type);
    setVal('dcfm_equipment_brand', item.brand);
    setVal('dcfm_equipment_spec', item.spec);
    setVal('dcfm_equipment_status', item.status);
    setVal('dcfm_equipment_detailed_loc', item.location);
    setVal('dc_location', item.dc);
    setVal('dcfm_equipment_supplier', item.supplier);
    setVal('dcfm_equipment_current_vendor', item.vendor);
    setVal('dcfm_equipment_serial_number', item.sn);
    setVal('dcfm_equipment_tm_asset_number', item['asset-tag']);
    setVal('dcfm_equipment_designed_lifespan', item.lifespan);
    setVal('dcfm_equipment_install_date', item.installed);
    setVal('dcfm_equipment_last_maintenance_date', item['latest-pm']);
    setVal('dcfm_equipment_note', item.notes);
    setVal('dcfm_equipment_create_by', item['created-by']);
    setVal('dcfm_equipment_created_date', item['date-created']);

    // Render history table
    renderHistoryTable(history);
    
  } catch (error) {
    console.error('Error loading equipment details:', error);
    alert('Failed to load equipment details: ' + error.message);
  }
}

function renderHistoryTable(entries) {
  const table = document.getElementById('equipment-history');
  if (!table) return;
  table.innerHTML = '';
  const thead = document.createElement('thead');
  const tbody = document.createElement('tbody');

  // determine columns from entries
  const allKeys = new Set();
  entries.forEach(e => Object.keys(e).forEach(k => allKeys.add(k)));
  const cols = Array.from(allKeys);
  // ensure some order if possible
  const prefer = ['id','date','event','notes','created-by','date-created'];
  cols.sort((a,b) => {
    const ai = prefer.indexOf(a); const bi = prefer.indexOf(b);
    if (ai===-1 && bi===-1) return a.localeCompare(b);
    if (ai===-1) return 1; if (bi===-1) return -1; return ai-bi;
  });

  // header
  const hr = document.createElement('tr');
  cols.forEach(c => { const th=document.createElement('th'); th.textContent=c; hr.appendChild(th); });
  thead.appendChild(hr);

  entries.forEach(ent => {
    const tr = document.createElement('tr');
    tr.style.verticalAlign = 'top';
    cols.forEach(c => {
      const td = document.createElement('td');
      td.textContent = ent[c] ?? '';
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });

  table.appendChild(thead);
  table.appendChild(tbody);

  // Update stats
  const statsEl = document.getElementById('equipment-history-stats');
  if (statsEl) {
    const count = entries.length;
    if (count === 0) {
      statsEl.value = 'No history found';
    } else {
      statsEl.value = `Found ${count} record${count !== 1 ? 's' : ''}`;
    }
  }
}
