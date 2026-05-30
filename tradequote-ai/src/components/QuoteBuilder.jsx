import { useState } from '@wordpress/element';
import { api } from './api';
import CustomerSearch from './CustomerSearch';
import LineItemEditor from './LineItemEditor';
import QuotePreview from './QuotePreview';

const { settings, adminUrl, freeQuota } = window.tqaData || {};

export default function QuoteBuilder() {
  const [description, setDescription] = useState('');
  const [lineItems, setLineItems]     = useState([]);
  const [notes, setNotes]             = useState('');
  const [customerId, setCustomerId]   = useState(null);
  const [customer, setCustomer]       = useState(null);
  const [generating, setGenerating]   = useState(false);
  const [saving, setSaving]           = useState(false);
  const [error, setError]             = useState('');
  const [success, setSuccess]         = useState('');
  const [tab, setTab]                 = useState('build'); // 'build' | 'preview'

  const generate = async () => {
    if (!description.trim()) { setError('Please enter a job description.'); return; }
    setError('');
    setGenerating(true);
    try {
      const result = await api.generateQuote(description);
      setLineItems(result.line_items);
      if (result.notes) setNotes(result.notes);
      setTab('build');
    } catch (e) {
      setError(e.message);
    } finally {
      setGenerating(false);
    }
  };

  const save = async (sendNow = false) => {
    if (!lineItems.length) { setError('Add at least one line item.'); return; }
    setError('');
    setSaving(true);
    try {
      const subtotal = lineItems.reduce((s, i) => s + parseFloat(i.total || 0), 0);
      const payload  = { customer_id: customerId, job_description: description, line_items: lineItems, subtotal, notes };
      const { id }   = await api.createQuote(payload);

      if (sendNow && customer?.email) {
        await api.sendQuote(id, {});
        setSuccess(`Quote created and emailed to ${customer.email}!`);
      } else {
        setSuccess('Quote saved as draft.');
      }

      setTimeout(() => {
        window.location.href = adminUrl + '?page=tqa-quotes';
      }, 1500);
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  };

  const subtotal  = lineItems.reduce((s, i) => s + parseFloat(i.total || 0), 0);
  const taxRate   = parseFloat(settings?.default_tax_rate || 0);
  const taxAmount = subtotal * (taxRate / 100);
  const total     = subtotal + taxAmount;

  return (
    <div className="tqa-builder">
      <div className="tqa-builder-main">
        {error   && <div className="tqa-alert tqa-alert-error">{error}</div>}
        {success && <div className="tqa-alert tqa-alert-success">{success}</div>}

        <div className="tqa-card">
          <h3 className="tqa-card-title">Customer</h3>
          <CustomerSearch
            value={customerId}
            onChange={(id, c) => { setCustomerId(id); setCustomer(c); }}
          />
        </div>

        <div className="tqa-card">
          <h3 className="tqa-card-title">Describe the Job</h3>
          <textarea
            className="tqa-textarea"
            rows={4}
            placeholder="e.g. Replace hot water cylinder, 3 hours labour, new 180L cylinder, copper fittings…"
            value={description}
            onChange={e => setDescription(e.target.value)}
          />
          <button
            className="tqa-btn tqa-btn-ai"
            onClick={generate}
            disabled={generating || !description.trim()}
          >
            {generating ? '⏳ Generating…' : '✨ Generate Line Items with AI'}
          </button>
        </div>

        {lineItems.length > 0 && (
          <div className="tqa-card">
            <div className="tqa-tabs">
              <button className={`tqa-tab ${tab === 'build' ? 'active' : ''}`} onClick={() => setTab('build')}>Edit Line Items</button>
              <button className={`tqa-tab ${tab === 'preview' ? 'active' : ''}`} onClick={() => setTab('preview')}>Preview Quote</button>
            </div>

            {tab === 'build' ? (
              <>
                <LineItemEditor items={lineItems} onChange={setLineItems} />
                <div style={{ marginTop: 12 }}>
                  <label className="tqa-label">Internal Notes</label>
                  <textarea
                    className="tqa-textarea"
                    rows={2}
                    value={notes}
                    onChange={e => setNotes(e.target.value)}
                    placeholder="Optional notes…"
                  />
                </div>
              </>
            ) : (
              <QuotePreview
                quote={{ line_items: lineItems, notes }}
                customer={customer}
                settings={settings}
              />
            )}

            <div className="tqa-totals-row">
              <span>Subtotal: ${subtotal.toFixed(2)}</span>
              {taxRate > 0 && <span>Tax ({taxRate}%): ${taxAmount.toFixed(2)}</span>}
              <span className="tqa-total-bold">Total: ${total.toFixed(2)}</span>
            </div>
          </div>
        )}

        <div className="tqa-actions">
          <button className="tqa-btn tqa-btn-secondary" onClick={() => save(false)} disabled={saving || !lineItems.length}>
            {saving ? 'Saving…' : 'Save as Draft'}
          </button>
          <button className="tqa-btn tqa-btn-primary" onClick={() => save(true)} disabled={saving || !lineItems.length || !customer?.email}>
            {saving ? 'Sending…' : 'Save & Send to Customer'}
          </button>
          {lineItems.length > 0 && !customer?.email && (
            <span className="tqa-hint">Add a customer email to send directly.</span>
          )}
        </div>
      </div>
    </div>
  );
}
