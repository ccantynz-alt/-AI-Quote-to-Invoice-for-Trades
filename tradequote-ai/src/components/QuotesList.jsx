import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

const { adminUrl } = window.tqaData || {};

const STATUS_COLORS = {
  draft:    '#6b7280',
  sent:     '#3b82f6',
  accepted: '#10b981',
  declined: '#ef4444',
  expired:  '#9ca3af',
};

export default function QuotesList() {
  const [quotes,   setQuotes]   = useState([]);
  const [loading,  setLoading]  = useState(true);
  const [filter,   setFilter]   = useState('');
  const [sending,  setSending]  = useState(null);
  const [converting, setConverting] = useState(null);
  const [error,    setError]    = useState('');

  const load = () => {
    setLoading(true);
    api.getQuotes({ limit: 100 })
      .then(setQuotes)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const sendQuote = async (id) => {
    if (!confirm('Send this quote to the customer?')) return;
    setSending(id);
    try {
      await api.sendQuote(id, {});
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setSending(null);
    }
  };

  const convert = async (id) => {
    const due = prompt('Due date for the invoice (YYYY-MM-DD):', new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0]);
    if (!due) return;
    setConverting(id);
    try {
      await api.convertToInvoice(id, { due_date: due });
      alert('Invoice created!');
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setConverting(null);
    }
  };

  const del = async (id) => {
    if (!confirm('Delete this quote?')) return;
    try {
      await api.deleteQuote(id);
      load();
    } catch (e) {
      setError(e.message);
    }
  };

  const filtered = filter ? quotes.filter(q => q.status === filter) : quotes;

  return (
    <div className="tqa-list">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      <div className="tqa-list-toolbar">
        <div className="tqa-filter-tabs">
          {['', 'draft', 'sent', 'accepted', 'declined', 'expired'].map(s => (
            <button
              key={s}
              className={`tqa-filter-tab ${filter === s ? 'active' : ''}`}
              onClick={() => setFilter(s)}
            >
              {s || 'All'}
            </button>
          ))}
        </div>
        <a href={adminUrl + '?page=tqa-new-quote'} className="tqa-btn tqa-btn-primary tqa-btn-sm">+ New Quote</a>
      </div>

      {loading ? (
        <div className="tqa-spinner">Loading…</div>
      ) : (
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
            {filtered.length === 0 && (
              <tr><td colSpan={7} style={{ textAlign: 'center', color: '#888', padding: 24 }}>No quotes found.</td></tr>
            )}
            {filtered.map(q => (
              <tr key={q.id}>
                <td><strong>{q.quote_number}</strong></td>
                <td>{q.customer_name || '–'}</td>
                <td style={{ maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {q.job_description || '–'}
                </td>
                <td style={{ textAlign: 'right' }}>${parseFloat(q.total || 0).toFixed(2)}</td>
                <td>
                  <span className="tqa-badge" style={{ background: STATUS_COLORS[q.status] + '22', color: STATUS_COLORS[q.status], borderColor: STATUS_COLORS[q.status] }}>
                    {q.status}
                  </span>
                </td>
                <td>{q.created_at?.split(' ')[0]}</td>
                <td>
                  <div className="tqa-row-actions">
                    {q.customer_email && q.status === 'draft' && (
                      <button
                        className="tqa-btn-link"
                        onClick={() => sendQuote(q.id)}
                        disabled={sending === q.id}
                      >
                        {sending === q.id ? 'Sending…' : 'Send'}
                      </button>
                    )}
                    {(q.status === 'sent' || q.status === 'accepted') && (
                      <button
                        className="tqa-btn-link tqa-btn-green"
                        onClick={() => convert(q.id)}
                        disabled={converting === q.id}
                      >
                        {converting === q.id ? '…' : '→ Invoice'}
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
