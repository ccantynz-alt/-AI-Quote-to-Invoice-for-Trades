import { useState, useEffect, useRef } from '@wordpress/element';
import { api } from './api';

export default function CustomerSearch({ value, onChange }) {
  const [query, setQuery]       = useState('');
  const [results, setResults]   = useState([]);
  const [open, setOpen]         = useState(false);
  const [selected, setSelected] = useState(null);
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
  };

  const createNew = async () => {
    const [name, email, phone] = [
      prompt('Customer name:', query),
      prompt('Email:'),
      prompt('Phone:'),
    ];
    if (!name) return;
    try {
      const { customer } = await api.createCustomer({ name, email: email || '', phone: phone || '' });
      pick(customer);
    } catch (e) {
      alert('Failed to create customer: ' + e.message);
    }
  };

  return (
    <div style={{ position: 'relative' }}>
      <input
        type="text"
        className="tqa-input"
        placeholder="Search customer name or email…"
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
            </div>
          ))}
          <div className="tqa-dropdown-item tqa-dropdown-new" onMouseDown={createNew}>
            + Create &ldquo;{query}&rdquo; as new customer
          </div>
        </div>
      )}
    </div>
  );
}
