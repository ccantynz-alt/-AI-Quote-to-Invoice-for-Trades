import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

export default function CustomersList() {
  const [customers, setCustomers] = useState([]);
  const [loading,   setLoading]   = useState(true);
  const [search,    setSearch]    = useState('');
  const [error,     setError]     = useState('');

  const load = (q = '') => {
    setLoading(true);
    api.getCustomers({ search: q, limit: 100 })
      .then(setCustomers)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const del = async (id) => {
    if (!confirm('Delete this customer?')) return;
    try {
      await api.deleteCustomer(id);
      load(search);
    } catch (e) {
      setError(e.message);
    }
  };

  let timer;
  const onSearch = (v) => {
    setSearch(v);
    clearTimeout(timer);
    timer = setTimeout(() => load(v), 300);
  };

  return (
    <div className="tqa-list">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      <div className="tqa-list-toolbar">
        <input
          type="search"
          className="tqa-input"
          placeholder="Search customers…"
          value={search}
          onChange={e => onSearch(e.target.value)}
          style={{ width: 260 }}
        />
      </div>

      {loading ? (
        <div className="tqa-spinner">Loading…</div>
      ) : (
        <table className="tqa-table tqa-table-full">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Since</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {customers.length === 0 && (
              <tr><td colSpan={6} style={{ textAlign: 'center', color: '#888', padding: 24 }}>No customers found.</td></tr>
            )}
            {customers.map(c => (
              <tr key={c.id}>
                <td><strong>{c.name}</strong></td>
                <td>{c.email ? <a href={`mailto:${c.email}`}>{c.email}</a> : '–'}</td>
                <td>{c.phone ? <a href={`tel:${c.phone}`}>{c.phone}</a> : '–'}</td>
                <td style={{ maxWidth: 180, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {c.address || '–'}
                </td>
                <td>{c.created_at?.split(' ')[0]}</td>
                <td>
                  <button className="tqa-btn-link tqa-btn-danger" onClick={() => del(c.id)}>Delete</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
