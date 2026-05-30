import { useState, useEffect, useRef } from '@wordpress/element';
import { api } from './api';

export default function CustomerSearch({ value, onChange }) {
  const [query,    setQuery]    = useState('');
  const [results,  setResults]  = useState([]);
  const [open,     setOpen]     = useState(false);
  const [selected, setSelected] = useState(null);
  const [creating, setCreating] = useState(false);
  const [newForm,  setNewForm]  = useState({ name: '', email: '', phone: '', address: '' });
  const [saving,   setSaving]   = useState(false);
  const [formErr,  setFormErr]  = useState('');
  const timer = useRef(null);

  useEffect(() => {
    if (value && !selected) {
      api.get(`customers/${value}`).then(c => {
        setSelected(c);
        setQuery(c.name);
      }).catch(() => {});
    }
  }, [value]);

  const search = (q) => {
    setQuery(q);
    setSelected(null);
    onChange(null, null);
    clearTimeout(timer.current);
    if (q.length < 2) { setResults([]); setOpen(false); return; }
    timer.current = setTimeout(async () => {
      try {
        const data = await api.getCustomers({ search: q, limit: 8 });
        setResults(data);
        setOpen(true);
      } catch {}
    }, 250);
  };

  const pick = (c) => {
    setSelected(c);
    setQuery(c.name);
    setResults([]);
    setOpen(false);
    onChange(c.id, c);
    setCreating(false);
  };

  const startCreate = () => {
    setOpen(false);
    setCreating(true);
    setNewForm({ name: query, email: '', phone: '', address: '' });
  };

  const saveNew = async () => {
    if (!newForm.name.trim()) { setFormErr('Name is required.'); return; }
    setSaving(true); setFormErr('');
    try {
      const { customer } = await api.createCustomer(newForm);
      pick(customer);
    } catch (e) {
      setFormErr(e.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div style={{ position: 'relative' }}>
      <input
        type="text"
        className="tqa-input"
        placeholder="Search by name or email…"
        value={query}
        onChange={e => search(e.target.value)}
        onFocus={() => results.length && setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 200)}
      />

      {open && (
        <div className="tqa-dropdown">
          {results.map(c => (
            <div key={c.id} className="tqa-dropdown-item" onMouseDown={() => pick(c)}>
              <strong>{c.name}</strong>
              {c.email && <span style={{ color: '#888', marginLeft: 8 }}>{c.email}</span>}
              {c.phone && <span style={{ color: '#9ca3af', marginLeft: 8, fontSize: 12 }}>{c.phone}</span>}
            </div>
          ))}
          {query.length >= 2 && (
            <div className="tqa-dropdown-item tqa-dropdown-new" onMouseDown={startCreate}>
              + Create new customer
            </div>
          )}
        </div>
      )}

      {creating && (
        <div className="tqa-new-customer-form">
          <div style={{ fontWeight: 600, marginBottom: 10, fontSize: 14 }}>New Customer</div>
          {formErr && <div className="tqa-alert tqa-alert-error" style={{ padding: '6px 10px', marginBottom: 8 }}>{formErr}</div>}
          {[
            { label: 'Name *', key: 'name',    type: 'text',  placeholder: 'Full name' },
            { label: 'Email',  key: 'email',   type: 'email', placeholder: 'email@example.com' },
            { label: 'Phone',  key: 'phone',   type: 'tel',   placeholder: '+64 21 000 0000' },
            { label: 'Address',key: 'address', type: 'text',  placeholder: '123 Main St, City' },
          ].map(f => (
            <div key={f.key} style={{ marginBottom: 8 }}>
              <label className="tqa-label">{f.label}</label>
              <input
                type={f.type}
                className="tqa-input tqa-input-sm"
                placeholder={f.placeholder}
                value={newForm[f.key]}
                onChange={e => setNewForm(p => ({ ...p, [f.key]: e.target.value }))}
              />
            </div>
          ))}
          <div style={{ display: 'flex', gap: 8, marginTop: 10 }}>
            <button className="tqa-btn tqa-btn-primary tqa-btn-sm" onClick={saveNew} disabled={saving}>
              {saving ? 'Saving…' : 'Create Customer'}
            </button>
            <button className="tqa-btn tqa-btn-secondary tqa-btn-sm" onClick={() => setCreating(false)}>Cancel</button>
          </div>
        </div>
      )}

      {selected && (
        <div style={{ marginTop: 6, fontSize: 13, color: '#374151' }}>
          <strong>{selected.name}</strong>
          {selected.email && <span style={{ color: '#6b7280', marginLeft: 8 }}>{selected.email}</span>}
          {selected.phone && <span style={{ color: '#6b7280', marginLeft: 8 }}>{selected.phone}</span>}
          <button
            style={{ marginLeft: 8, fontSize: 12, color: '#1a56db', background: 'none', border: 'none', cursor: 'pointer', textDecoration: 'underline' }}
            onClick={() => { setSelected(null); setQuery(''); onChange(null, null); }}
          >
            Change
          </button>
        </div>
      )}
    </div>
  );
}
