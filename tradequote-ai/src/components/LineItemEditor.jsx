import { useState } from '@wordpress/element';

const empty = () => ({ description: '', qty: 1, unit: 'ea', unit_price: 0, total: 0 });

export default function LineItemEditor({ items, onChange }) {
  const update = (idx, field, val) => {
    const next = items.map((item, i) => {
      if (i !== idx) return item;
      const updated = { ...item, [field]: val };
      if (field === 'qty' || field === 'unit_price') {
        updated.total = parseFloat((updated.qty * updated.unit_price).toFixed(2));
      }
      return updated;
    });
    onChange(next);
  };

  const add    = () => onChange([...items, empty()]);
  const remove = (idx) => onChange(items.filter((_, i) => i !== idx));

  const subtotal = items.reduce((s, i) => s + parseFloat(i.total || 0), 0);

  return (
    <div className="tqa-line-items">
      <table className="tqa-table">
        <thead>
          <tr>
            <th style={{ width: '40%' }}>Description</th>
            <th style={{ width: '8%',  textAlign: 'center' }}>Qty</th>
            <th style={{ width: '8%',  textAlign: 'center' }}>Unit</th>
            <th style={{ width: '18%', textAlign: 'right'  }}>Unit Price</th>
            <th style={{ width: '18%', textAlign: 'right'  }}>Total</th>
            <th style={{ width: '8%'  }}></th>
          </tr>
        </thead>
        <tbody>
          {items.map((item, idx) => (
            <tr key={idx}>
              <td>
                <input
                  className="tqa-input tqa-input-sm"
                  value={item.description}
                  onChange={e => update(idx, 'description', e.target.value)}
                  placeholder="Description"
                />
              </td>
              <td>
                <input
                  className="tqa-input tqa-input-sm tqa-input-center"
                  type="number"
                  value={item.qty}
                  min="0"
                  step="0.5"
                  onChange={e => update(idx, 'qty', parseFloat(e.target.value) || 0)}
                />
              </td>
              <td>
                <select
                  className="tqa-input tqa-input-sm tqa-input-center"
                  value={item.unit}
                  onChange={e => update(idx, 'unit', e.target.value)}
                >
                  {['ea','hr','m','m2','m3','ls','day','bag','sheet','roll'].map(u => (
                    <option key={u} value={u}>{u}</option>
                  ))}
                </select>
              </td>
              <td>
                <input
                  className="tqa-input tqa-input-sm tqa-input-right"
                  type="number"
                  value={item.unit_price}
                  min="0"
                  step="0.01"
                  onChange={e => update(idx, 'unit_price', parseFloat(e.target.value) || 0)}
                />
              </td>
              <td style={{ textAlign: 'right', fontWeight: 500, paddingRight: 8 }}>
                ${parseFloat(item.total || 0).toFixed(2)}
              </td>
              <td>
                <button className="tqa-btn-ghost tqa-btn-danger" onClick={() => remove(idx)} title="Remove">✕</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 }}>
        <button className="tqa-btn tqa-btn-sm tqa-btn-secondary" onClick={add}>+ Add Line Item</button>
        <div style={{ fontWeight: 600, fontSize: 15 }}>Subtotal: ${subtotal.toFixed(2)}</div>
      </div>
    </div>
  );
}
