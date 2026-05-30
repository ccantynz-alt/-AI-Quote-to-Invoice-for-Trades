import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

const STATUS_COLORS = {
  draft:     '#6b7280',
  sent:      '#3b82f6',
  paid:      '#059669',
  overdue:   '#ef4444',
  cancelled: '#9ca3af',
};

export default function InvoicesList() {
  const [invoices, setInvoices] = useState([]);
  const [loading,  setLoading]  = useState(true);
  const [filter,   setFilter]   = useState('');
  const [sending,  setSending]  = useState(null);
  const [error,    setError]    = useState('');

  const load = () => {
    setLoading(true);
    api.getInvoices({ limit: 100 })
      .then(setInvoices)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const send = async (id) => {
    if (!confirm('Send this invoice to the customer?')) return;
    setSending(id);
    try {
      await api.sendInvoice(id, {});
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setSending(null);
    }
  };

  const markPaid = async (id) => {
    if (!confirm('Mark this invoice as paid?')) return;
    try {
      await api.markPaid(id);
      load();
    } catch (e) {
      setError(e.message);
    }
  };

  const del = async (id) => {
    if (!confirm('Delete this invoice?')) return;
    try {
      await api.deleteInvoice(id);
      load();
    } catch (e) {
      setError(e.message);
    }
  };

  const filtered = filter ? invoices.filter(i => i.status === filter) : invoices;

  return (
    <div className="tqa-list">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      <div className="tqa-list-toolbar">
        <div className="tqa-filter-tabs">
          {['', 'draft', 'sent', 'paid', 'overdue', 'cancelled'].map(s => (
            <button
              key={s}
              className={`tqa-filter-tab ${filter === s ? 'active' : ''}`}
              onClick={() => setFilter(s)}
            >
              {s || 'All'}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="tqa-spinner">Loading…</div>
      ) : (
        <table className="tqa-table tqa-table-full">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Customer</th>
              <th style={{ textAlign: 'right' }}>Total</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 && (
              <tr><td colSpan={6} style={{ textAlign: 'center', color: '#888', padding: 24 }}>No invoices found.</td></tr>
            )}
            {filtered.map(inv => (
              <tr key={inv.id}>
                <td><strong>{inv.invoice_number}</strong></td>
                <td>{inv.customer_name || '–'}</td>
                <td style={{ textAlign: 'right' }}>${parseFloat(inv.total || 0).toFixed(2)}</td>
                <td>
                  <span className="tqa-badge" style={{ background: STATUS_COLORS[inv.status] + '22', color: STATUS_COLORS[inv.status], borderColor: STATUS_COLORS[inv.status] }}>
                    {inv.status}
                  </span>
                </td>
                <td>{inv.due_date || '–'}</td>
                <td>
                  <div className="tqa-row-actions">
                    {inv.customer_email && inv.status === 'draft' && (
                      <button className="tqa-btn-link" onClick={() => send(inv.id)} disabled={sending === inv.id}>
                        {sending === inv.id ? 'Sending…' : 'Send'}
                      </button>
                    )}
                    {inv.status !== 'paid' && (
                      <button className="tqa-btn-link tqa-btn-green" onClick={() => markPaid(inv.id)}>Mark Paid</button>
                    )}
                    <button className="tqa-btn-link tqa-btn-danger" onClick={() => del(inv.id)}>Delete</button>
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
