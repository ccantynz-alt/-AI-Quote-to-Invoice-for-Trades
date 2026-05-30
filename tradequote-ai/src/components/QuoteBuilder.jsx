import { useState } from '@wordpress/element';
import { api } from './api';
import CustomerSearch from './CustomerSearch';
import LineItemEditor from './LineItemEditor';
import QuotePreview from './QuotePreview';

const { settings, adminUrl, freeQuota, quotaRemaining, isPro, tradeTypes, currency } = window.tqaData || {};

const TRADE_LABELS = {
  general:     'General / Other',
  plumber:     'Plumber',
  electrician: 'Electrician',
  builder:     'Builder',
  painter:     'Painter',
  landscaper:  'Landscaper',
  hvac:        'HVAC',
  cleaner:     'Cleaner',
  roofer:      'Roofer',
  tiler:       'Tiler',
};

const JOB_EXAMPLES = {
  general:     'e.g. Supply and install new gate, 4 hours labour, materials',
  plumber:     'e.g. Replace hot water cylinder, 3 hours labour, new 180L cylinder, fittings',
  electrician: 'e.g. Install new switchboard, upgrade 4 circuits, 5 hours labour, parts',
  builder:     'e.g. Build deck 4m x 6m, treated pine framing, composite decking, 3 days labour',
  painter:     'e.g. Exterior repaint, prep and 2 coats, 120m2, include primer',
  landscaper:  'e.g. Lawn renovation, aerate, oversow, top dress, 80m2 lawn',
  hvac:        'e.g. Supply and install 3.5kW heat pump, include commissioning and 5m lineset',
  cleaner:     'e.g. Commercial office deep clean 300m2, 2 cleaners, 4 hours, products included',
  roofer:      'e.g. Replace roof 180m2, new Colorsteel long-run, new flashings, guttering',
  tiler:       'e.g. Bathroom tile 18m2 floor and walls, 600x600 tiles, waterproofing, grout',
};

export default function QuoteBuilder() {
  const [description, setDescription] = useState('');
  const [tradeType,   setTradeType]   = useState('general');
  const [lineItems,   setLineItems]   = useState([]);
  const [notes,       setNotes]       = useState('');
  const [customerId,  setCustomerId]  = useState(null);
  const [customer,    setCustomer]    = useState(null);
  const [generating,  setGenerating]  = useState(false);
  const [saving,      setSaving]      = useState(false);
  const [error,       setError]       = useState('');
  const [success,     setSuccess]     = useState('');
  const [tab,         setTab]         = useState('build');
  const [acceptUrl,   setAcceptUrl]   = useState('');
  const [copyDone,    setCopyDone]    = useState(false);

  const sym = currency?.symbol || '$';

  const isQuotaExceeded = !isPro && (quotaRemaining ?? freeQuota) <= 0;

  const generate = async () => {
    if (!description.trim()) { setError('Please enter a job description.'); return; }
    setError(''); setGenerating(true);
    try {
      const result = await api.generateQuote(description, tradeType);
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
    setError(''); setSaving(true);
    try {
      const subtotal = lineItems.reduce((s, i) => s + parseFloat(i.total || 0), 0);
      const payload  = { customer_id: customerId, job_description: description, line_items: lineItems, subtotal, notes };
      const { id, quote } = await api.createQuote(payload);

      if (quote?.acceptance_url) setAcceptUrl(quote.acceptance_url);

      if (sendNow && customer?.email) {
        await api.sendQuote(id, {});
        setSuccess(`Quote created and emailed to ${customer.email}!`);
      } else {
        setSuccess('Quote saved as draft.');
      }

      setTimeout(() => { window.location.href = adminUrl + '?page=tqa-quotes'; }, 1800);
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  };

  const copyAcceptUrl = () => {
    navigator.clipboard.writeText(acceptUrl).then(() => {
      setCopyDone(true);
      setTimeout(() => setCopyDone(false), 2000);
    });
  };

  const subtotal  = lineItems.reduce((s, i) => s + parseFloat(i.total || 0), 0);
  const taxRate   = parseFloat(settings?.default_tax_rate || 0);
  const taxAmount = subtotal * (taxRate / 100);
  const total     = subtotal + taxAmount;

  return (
    <div className="tqa-builder">
      <div className="tqa-builder-main">
        {error   && <div className="tqa-alert tqa-alert-error" dangerouslySetInnerHTML={{ __html: error }} />}
        {success && <div className="tqa-alert tqa-alert-success">{success}</div>}

        {isQuotaExceeded && (
          <div className="tqa-alert tqa-alert-error">
            You've used all {freeQuota} free quotes this month.{' '}
            <a href="https://tradequoteai.com/#pricing" target="_blank" rel="noopener">Upgrade to Pro</a> for unlimited quotes.
          </div>
        )}

        {!isPro && !isQuotaExceeded && (quotaRemaining ?? freeQuota) <= 2 && (
          <div className="tqa-alert" style={{ background: '#fffbeb', borderColor: '#f59e0b', color: '#92400e' }}>
            {quotaRemaining ?? freeQuota} free quote{(quotaRemaining ?? freeQuota) !== 1 ? 's' : ''} remaining this month.{' '}
            <a href="https://tradequoteai.com/#pricing" target="_blank" rel="noopener">Upgrade to Pro →</a>
          </div>
        )}

        <div className="tqa-card">
          <h3 className="tqa-card-title">Customer</h3>
          <CustomerSearch
            value={customerId}
            onChange={(id, c) => { setCustomerId(id); setCustomer(c); }}
          />
        </div>

        <div className="tqa-card">
          <h3 className="tqa-card-title">Job Details</h3>
          <div style={{ marginBottom: 12 }}>
            <label className="tqa-label">Trade Type</label>
            <select
              className="tqa-input"
              style={{ maxWidth: 240 }}
              value={tradeType}
              onChange={e => setTradeType(e.target.value)}
            >
              {(tradeTypes || Object.keys(TRADE_LABELS)).map(t => (
                <option key={t} value={t}>{TRADE_LABELS[t] || t}</option>
              ))}
            </select>
          </div>
          <label className="tqa-label">Job Description</label>
          <textarea
            className="tqa-textarea"
            rows={4}
            placeholder={JOB_EXAMPLES[tradeType] || JOB_EXAMPLES.general}
            value={description}
            onChange={e => setDescription(e.target.value)}
            disabled={isQuotaExceeded}
          />
          <button
            className="tqa-btn tqa-btn-ai"
            onClick={generate}
            disabled={generating || !description.trim() || isQuotaExceeded}
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
                    placeholder="Optional notes visible only to you…"
                  />
                </div>
              </>
            ) : (
              <QuotePreview quote={{ line_items: lineItems, notes }} customer={customer} settings={settings} />
            )}

            <div className="tqa-totals-row">
              <span>Subtotal: {sym}{subtotal.toFixed(2)}</span>
              {taxRate > 0 && <span>Tax ({taxRate}%): {sym}{taxAmount.toFixed(2)}</span>}
              <span className="tqa-total-bold">Total: {sym}{total.toFixed(2)}</span>
            </div>
          </div>
        )}

        {acceptUrl && (
          <div className="tqa-card" style={{ background: '#f0fdf4', borderColor: '#86efac' }}>
            <h3 className="tqa-card-title" style={{ color: '#166534' }}>Customer Acceptance Link</h3>
            <p style={{ fontSize: 13, color: '#166534', marginBottom: 8 }}>Share this link with your customer so they can accept or decline the quote online:</p>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <input type="text" readOnly className="tqa-input" value={acceptUrl} style={{ maxWidth: 420 }} />
              <button className="tqa-btn tqa-btn-secondary tqa-btn-sm" onClick={copyAcceptUrl}>
                {copyDone ? '✓ Copied' : 'Copy'}
              </button>
            </div>
          </div>
        )}

        <div className="tqa-actions">
          <button className="tqa-btn tqa-btn-secondary" onClick={() => save(false)} disabled={saving || !lineItems.length || isQuotaExceeded}>
            {saving ? 'Saving…' : 'Save as Draft'}
          </button>
          <button className="tqa-btn tqa-btn-primary" onClick={() => save(true)} disabled={saving || !lineItems.length || !customer?.email || isQuotaExceeded}>
            {saving ? 'Sending…' : '📧 Save & Send to Customer'}
          </button>
          {lineItems.length > 0 && !customer?.email && (
            <span className="tqa-hint">Add a customer with an email address to send directly.</span>
          )}
        </div>
      </div>
    </div>
  );
}
