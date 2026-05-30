import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

const { adminUrl, adminPostUrl, pdfNonce, currency } = window.tqaData || {};

const STATUS_COLORS = {
  draft:    '#6b7280',
  sent:     '#3b82f6',
  accepted: '#10b981',
  declined: '#ef4444',
  expired:  '#9ca3af',
};

export default function QuotesList() {
  const [quotes,     setQuotes]     = useState([]);
  const [loading,    setLoading]    = useState(true);
  const [filter,     setFilter]     = useState('');
  const [search,     setSearch]     = useState('');
  const [sending,    setSending]    = useState(null);
  const [converting, setConverting] = useState(null);
  const [dueDate,    setDueDate]    = useState({ id: null, value: '' });
  const [error,      setError]      = useState('');
  const sym = currency?.symbol || '$';

  const load = () => {
    setLoading(true);
    const params = { limit: 100 };
    if (filter) params.status = filter;
    if (search) params.search = search;
    api.getQuotes(params)
      .then(setQuotes)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(load, [filter]);

  let searchTimer;
  const onSearch = (v) => {
    setSearch(v);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(load, 350);
  };

  const pdfUrl = (id) =>
    `${adminPostUrl}?action=tqa_download_quote_pdf&id=${id}&_wpnonce=${pdfNonce}`;

  const sendQuote = async (id) => {
    if (!confirm('Send this quote to the customer?')) return;
    setSending(id);
    try { await api.sendQuote(id, {}); load(); }
    catch (e) { setError(e.message); }
    finally { setSending(null); }
  };

  const startConvert = (id) => {
    const defaultDate = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0];
    setDueDate({ id, value: defaultDate });
  };

  const confirmConvert = async () => {
    const { id, value } = dueDate;
    if (!value) return;
    setConverting(id);
    setDueDate({ id: null, value: '' });
    try {
      await api.convertToInvoice(id, { due_date: value });
      load();
    } catch (e) { setError(e.message); }
    finally { setConverting(null); }
  };

  const copyAcceptUrl = async (id) => {
    try {
      const { url } = await api.get(`quotes/${id}/acceptance-url`);
      navigator.clipboard.writeText(url).then(() => alert('Customer acceptance link copied to clipboard!'));
    } catch (e) { setError(e.message); }
  };

  const del = async (id) => {
    if (!confirm('Delete this quote?')) return;
    try { await api.deleteQuote(id); load(); }
    catch (e) { setError(e.message); }
  };

  return (
    <div className="tqa-list">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      {dueDate.id && (
        <div className="tqa-modal-overlay">
          <div className="tqa-modal">
            <h3 style={{ marginBottom: 12 }}>Convert to Invoice</h3>
            <label className="tqa-label">Due Date</label>
            <input type="date" className="tqa-input" value={dueDate.value} onChange={e => setDueDate(d => ({ ...d, value: e.target.value }))} />
            <div style={{ display: 'flex', gap: 8, marginTop: 16 }}>
              <button className="tqa-btn tqa-btn-primary" onClick={confirmConvert}>Create Invoice</button>
              <button className="tqa-btn tqa-btn-secondary" onClick={() => setDueDate({ id: null, value: '' })}>Cancel</button>
            </div>
          </div>
        </div>
      )}

      <div className="tqa-list-toolbar">
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
          <div className="tqa-filter-tabs">
            {['', 'draft', 'sent', 'accepted', 'declined', 'expired'].map(s => (
              <button key={s} className={`tqa-filter-tab ${filter === s ? 'active' : ''}`} onClick={() => setFilter(s)}>
                {s || 'All'}
              </button>
            ))}
          </div>
          <input type="search" className="tqa-input tqa-input-sm" placeholder="Search…" style={{ width: 180 }}
            value={search} onChange={e => onSearch(e.target.value)} />
        </div>
        <a href={adminUrl + '?page=tqa-new-quote'} className="tqa-btn tqa-btn-primary tqa-btn-sm">+ New Quote</a>
      </div>

      {loading ? <div className="tqa-spinner">Loading…</div> : (
        <table className="tqa-table tqa-table-full">
          <thead>
            <tr>
              <th>Quote #</th>
              <th>Customer</th>
              <th>Description</th>
              <th style={{ textAlign: 'right' }}>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {quotes.length === 0 && (
              <tr><td colSpan={7} style={{ textAlign: 'center', color: '#888', padding: 32 }}>
                No quotes found. <a href={adminUrl + '?page=tqa-new-quote'}>Create your first quote →</a>
              </td></tr>
            )}
            {quotes.map(q => (
              <tr key={q.id}>
                <td><strong>{q.quote_number}</strong></td>
                <td>
                  <div>{q.customer_name || '–'}</div>
                  {q.customer_email && <div style={{ fontSize: 11, color: '#9ca3af' }}>{q.customer_email}</div>}
                </td>
                <td style={{ maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {q.job_description || '–'}
                </td>
                <td style={{ textAlign: 'right', fontWeight: 600 }}>{sym}{parseFloat(q.total || 0).toFixed(2)}</td>
                <td>
                  <span className="tqa-badge" style={{ background: (STATUS_COLORS[q.status] || '#888') + '22', color: STATUS_COLORS[q.status] || '#888', borderColor: STATUS_COLORS[q.status] || '#888' }}>
                    {q.status}
                  </span>
                </td>
                <td style={{ whiteSpace: 'nowrap' }}>{q.created_at?.split(' ')[0]}</td>
                <td>
                  <div className="tqa-row-actions">
                    <a href={pdfUrl(q.id)} className="tqa-btn-link" target="_blank" rel="noopener">PDF</a>
                    {q.customer_email && q.status === 'draft' && (
                      <button className="tqa-btn-link" onClick={() => sendQuote(q.id)} disabled={sending === q.id}>
                        {sending === q.id ? '…' : 'Send'}
                      </button>
                    )}
                    {(q.status === 'sent' || q.status === 'accepted') && (
                      <button className="tqa-btn-link tqa-btn-green" onClick={() => startConvert(q.id)} disabled={converting === q.id}>
                        {converting === q.id ? '…' : '→ Invoice'}
                      </button>
                    )}
                    {(q.status === 'draft' || q.status === 'sent') && (
                      <button className="tqa-btn-link" onClick={() => copyAcceptUrl(q.id)} title="Copy customer acceptance link" style={{ color: '#6b7280' }}>
                        🔗 Link
                      </button>
                    )}
                    <button className="tqa-btn-link tqa-btn-danger" onClick={() => del(q.id)}>Delete</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
