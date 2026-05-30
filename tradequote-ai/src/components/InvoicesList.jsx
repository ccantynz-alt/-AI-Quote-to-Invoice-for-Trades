import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

const { adminPostUrl, pdfNonce, currency } = window.tqaData || {};

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
  const sym = currency?.symbol || '$';

  const load = () => {
    setLoading(true);
    const params = { limit: 100 };
    if (filter) params.status = filter;
    api.getInvoices(params)
      .then(setInvoices)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  };

  useEffect(load, [filter]);

  const pdfUrl = (id) =>
    `${adminPostUrl}?action=tqa_download_invoice_pdf&id=${id}&_wpnonce=${pdfNonce}`;

  const send = async (id) => {
    if (!confirm('Send this invoice to the customer?')) return;
    setSending(id);
    try { await api.sendInvoice(id, {}); load(); }
    catch (e) { setError(e.message); }
    finally { setSending(null); }
  };

  const markPaid = async (id) => {
    if (!confirm('Mark this invoice as paid?')) return;
    try { await api.markPaid(id); load(); }
    catch (e) { setError(e.message); }
  };

  const del = async (id) => {
    if (!confirm('Delete this invoice?')) return;
    try { await api.deleteInvoice(id); load(); }
    catch (e) { setError(e.message); }
  };

  const totalOutstanding = invoices
    .filter(i => i.status === 'sent' || i.status === 'overdue')
    .reduce((s, i) => s + parseFloat(i.total || 0), 0);

  return (
    <div className="tqa-list">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      {totalOutstanding > 0 && (
        <div className="tqa-alert" style={{ background: '#fff7ed', borderColor: '#fdba74', color: '#9a3412' }}>
          <strong>{sym}{totalOutstanding.toFixed(2)}</strong> outstanding across {invoices.filter(i => i.status === 'sent' || i.status === 'overdue').length} invoice(s).
        </div>
      )}

      <div className="tqa-list-toolbar">
        <div className="tqa-filter-tabs">
          {['', 'draft', 'sent', 'paid', 'overdue', 'cancelled'].map(s => (
            <button key={s} className={`tqa-filter-tab ${filter === s ? 'active' : ''}`} onClick={() => setFilter(s)}>
              {s || 'All'}
            </button>
          ))}
        </div>
      </div>

      {loading ? <div className="tqa-spinner">Loading…</div> : (
        <table className="tqa-table tqa-table-full">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Customer</th>
              <th style={{ textAlign: 'right' }}>Total</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Paid</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {invoices.length === 0 && (
              <tr><td colSpan={7} style={{ textAlign: 'center', color: '#888', padding: 32 }}>
                No invoices yet. Convert an accepted quote to create one.
              </td></tr>
            )}
            {invoices.map(inv => (
              <tr key={inv.id}>
                <td><strong>{inv.invoice_number}</strong></td>
                <td>
                  <div>{inv.customer_name || '–'}</div>
                  {inv.customer_email && <div style={{ fontSize: 11, color: '#9ca3af' }}>{inv.customer_email}</div>}
                </td>
                <td style={{ textAlign: 'right', fontWeight: 600 }}>{sym}{parseFloat(inv.total || 0).toFixed(2)}</td>
                <td>
                  <span className="tqa-badge" style={{ background: (STATUS_COLORS[inv.status] || '#888') + '22', color: STATUS_COLORS[inv.status] || '#888', borderColor: STATUS_COLORS[inv.status] || '#888' }}>
                    {inv.status}
                  </span>
                </td>
                <td style={{ whiteSpace: 'nowrap' }}>
                  {inv.due_date || '–'}
                  {inv.status !== 'paid' && inv.due_date && new Date(inv.due_date) < new Date() && (
                    <span style={{ marginLeft: 6, color: '#ef4444', fontSize: 11, fontWeight: 600 }}>OVERDUE</span>
                  )}
                </td>
                <td style={{ whiteSpace: 'nowrap', color: '#059669' }}>{inv.paid_at?.split(' ')[0] || '–'}</td>
                <td>
                  <div className="tqa-row-actions">
                    <a href={pdfUrl(inv.id)} className="tqa-btn-link" target="_blank" rel="noopener">PDF</a>
                    {inv.customer_email && inv.status === 'draft' && (
                      <button className="tqa-btn-link" onClick={() => send(inv.id)} disabled={sending === inv.id}>
                        {sending === inv.id ? '…' : 'Send'}
                      </button>
                    )}
                    {inv.status !== 'paid' && inv.status !== 'cancelled' && (
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
