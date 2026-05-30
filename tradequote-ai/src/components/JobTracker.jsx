import { useState, useEffect } from '@wordpress/element';
import { api } from './api';

const { adminUrl } = window.tqaData || {};

const COLUMNS = [
  { key: 'draft',    label: 'Draft',    color: '#6b7280' },
  { key: 'sent',     label: 'Sent',     color: '#3b82f6' },
  { key: 'accepted', label: 'Accepted', color: '#10b981' },
  { key: 'invoiced', label: 'Invoiced', color: '#f59e0b' },
  { key: 'paid',     label: 'Paid',     color: '#059669' },
];

export default function JobTracker() {
  const [quotes,   setQuotes]   = useState([]);
  const [invoices, setInvoices] = useState([]);
  const [loading,  setLoading]  = useState(true);

  useEffect(() => {
    Promise.all([
      api.getQuotes({ limit: 100 }),
      api.getInvoices({ limit: 100 }),
    ]).then(([q, inv]) => {
      setQuotes(q);
      setInvoices(inv);
    }).finally(() => setLoading(false));
  }, []);

  if (loading) return <div className="tqa-spinner">Loading…</div>;

  // Map invoiced quotes — quotes that have an associated invoice.
  const invoicedQuoteIds = new Set(invoices.map(i => i.quote_id).filter(Boolean));

  const paidInvoices = invoices.filter(i => i.status === 'paid');

  const getColumn = (key) => {
    if (key === 'paid')     return paidInvoices;
    if (key === 'invoiced') return invoices.filter(i => i.status !== 'paid');
    return quotes.filter(q => {
      if (key === 'accepted') return q.status === 'accepted' && !invoicedQuoteIds.has(q.id);
      return q.status === key;
    });
  };

  const daysSince = (dateStr) => {
    const diff = Date.now() - new Date(dateStr).getTime();
    return Math.floor(diff / 86400000);
  };

  const fmt = (n) => '$' + parseFloat(n || 0).toFixed(0);

  return (
    <div className="tqa-kanban">
      {COLUMNS.map(col => {
        const items = getColumn(col.key);
        return (
          <div key={col.key} className="tqa-kanban-col">
            <div className="tqa-kanban-header" style={{ borderTopColor: col.color }}>
              <span style={{ color: col.color, fontWeight: 700 }}>{col.label}</span>
              <span className="tqa-kanban-count">{items.length}</span>
            </div>
            <div className="tqa-kanban-cards">
              {items.length === 0 && <div className="tqa-kanban-empty">No items</div>}
              {items.map(item => (
                <KanbanCard
                  key={(item.invoice_number ? 'inv-' : 'q-') + item.id}
                  item={item}
                  col={col.key}
                  daysSince={daysSince}
                  fmt={fmt}
                />
              ))}
            </div>
          </div>
        );
      })}
    </div>
  );
}

function KanbanCard({ item, col, daysSince, fmt }) {
  const isInvoice = !!item.invoice_number;
  const num       = item.invoice_number || item.quote_number;
  const name      = item.customer_name || 'Unknown';
  const days      = daysSince(item.created_at);
  const { adminUrl } = window.tqaData || {};

  return (
    <div className="tqa-kanban-card">
      <div className="tqa-kanban-card-num">{num}</div>
      <div className="tqa-kanban-card-customer">{name}</div>
      {item.job_description && (
        <div className="tqa-kanban-card-desc">{item.job_description.substring(0, 60)}{item.job_description.length > 60 ? '…' : ''}</div>
      )}
      <div className="tqa-kanban-card-footer">
        <span className="tqa-kanban-card-total">{fmt(item.total)}</span>
        <span className="tqa-kanban-card-age">{days}d</span>
      </div>
      <div className="tqa-kanban-card-actions">
        {!isInvoice && (
          <a href={`${adminUrl}?page=tqa-quotes&id=${item.id}`} className="tqa-btn-link">View</a>
        )}
        {isInvoice && (
          <a href={`${adminUrl}?page=tqa-invoices&id=${item.id}`} className="tqa-btn-link">Invoice</a>
        )}
      </div>
    </div>
  );
}
