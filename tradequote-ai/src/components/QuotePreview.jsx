export default function QuotePreview({ quote, customer, settings }) {
  const subtotal   = quote.line_items.reduce((s, i) => s + parseFloat(i.total || 0), 0);
  const taxRate    = parseFloat(settings?.default_tax_rate || 0);
  const taxAmount  = subtotal * (taxRate / 100);
  const total      = subtotal + taxAmount;

  return (
    <div className="tqa-preview">
      <div className="tqa-preview-header">
        <div>
          <div className="tqa-preview-biz">{settings?.business_name || 'Your Business'}</div>
          {settings?.tax_number && <div className="tqa-preview-sub">Tax No: {settings.tax_number}</div>}
        </div>
        <div style={{ textAlign: 'right' }}>
          <div className="tqa-preview-doc-title">QUOTE</div>
          {customer && <div className="tqa-preview-sub">{customer.name}</div>}
        </div>
      </div>

      {quote.line_items.length > 0 && (
        <table className="tqa-table tqa-preview-table">
          <thead>
            <tr>
              <th>Description</th>
              <th style={{ textAlign: 'center' }}>Qty</th>
              <th style={{ textAlign: 'center' }}>Unit</th>
              <th style={{ textAlign: 'right'  }}>Price</th>
              <th style={{ textAlign: 'right'  }}>Total</th>
            </tr>
          </thead>
          <tbody>
            {quote.line_items.map((item, i) => (
              <tr key={i}>
                <td>{item.description}</td>
                <td style={{ textAlign: 'center' }}>{item.qty}</td>
                <td style={{ textAlign: 'center' }}>{item.unit}</td>
                <td style={{ textAlign: 'right'  }}>${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                <td style={{ textAlign: 'right'  }}>${parseFloat(item.total || 0).toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <div className="tqa-preview-totals">
        <div className="tqa-preview-total-row"><span>Subtotal</span><span>${subtotal.toFixed(2)}</span></div>
        {taxRate > 0 && (
          <div className="tqa-preview-total-row"><span>Tax ({taxRate}%)</span><span>${taxAmount.toFixed(2)}</span></div>
        )}
        <div className="tqa-preview-total-row tqa-preview-grand"><span>Total</span><span>${total.toFixed(2)}</span></div>
      </div>

      {quote.notes && <div className="tqa-preview-notes"><strong>Notes:</strong> {quote.notes}</div>}
    </div>
  );
}
