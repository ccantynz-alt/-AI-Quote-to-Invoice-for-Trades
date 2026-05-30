import { useState, useEffect, useRef } from '@wordpress/element';
import { api } from './api';

const { pluginUrl } = window.tqaData || {};

export default function SettingsPage() {
  const [form,     setForm]     = useState(null);
  const [saving,   setSaving]   = useState(false);
  const [testing,  setTesting]  = useState(false);
  const [keyValid, setKeyValid] = useState(null); // null | true | false
  const [saved,    setSaved]    = useState(false);
  const [error,    setError]    = useState('');
  const logoRef = useRef(null);

  useEffect(() => {
    api.getSettings().then(s => setForm(s)).catch(e => setError(e.message));
  }, []);

  const set = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const save = async () => {
    setSaving(true); setSaved(false); setError('');
    try {
      await api.saveSettings(form);
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  };

  const testKey = async () => {
    if (!form.claude_api_key || form.claude_api_key.includes('•')) {
      setError('Enter a new API key to test it.');
      return;
    }
    setTesting(true); setKeyValid(null);
    try {
      const res = await api.post('ai/test-key', { api_key: form.claude_api_key });
      setKeyValid(res.valid);
    } catch (e) {
      setKeyValid(false);
      setError(e.message);
    } finally {
      setTesting(false);
    }
  };

  const openMediaUploader = () => {
    if (logoRef.current) { logoRef.current.click(); return; }
    if (typeof wp === 'undefined' || !wp.media) {
      alert('WordPress media uploader not available.');
      return;
    }
    const frame = wp.media({
      title: 'Select Logo',
      button: { text: 'Use this image' },
      multiple: false,
      library: { type: 'image' },
    });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      set('business_logo', attachment.url);
    });
    frame.open();
  };

  if (!form) return <div className="tqa-spinner">Loading settings…</div>;

  const Field = ({ label, name, type = 'text', placeholder, help, children }) => (
    <tr>
      <th style={{ width: 220, paddingTop: 14, verticalAlign: 'top' }}>
        <label htmlFor={`tqa-${name}`} style={{ fontWeight: 600 }}>{label}</label>
      </th>
      <td>
        {children || (
          <input
            id={`tqa-${name}`}
            type={type}
            className="tqa-input"
            style={{ maxWidth: 360 }}
            value={form[name] || ''}
            placeholder={placeholder || ''}
            onChange={e => set(name, e.target.value)}
          />
        )}
        {help && <p className="description" style={{ marginTop: 4 }}>{help}</p>}
      </td>
    </tr>
  );

  return (
    <div className="tqa-settings">
      {error  && <div className="tqa-alert tqa-alert-error">{error}</div>}
      {saved  && <div className="tqa-alert tqa-alert-success">Settings saved.</div>}

      {/* Welcome banner on first activation */}
      {window.tqaData?.welcome && (
        <div className="tqa-alert tqa-alert-success" style={{ fontSize: 15, marginBottom: 20 }}>
          🎉 <strong>TradeQuote AI is installed!</strong> Fill in your business details below and add your Claude API key to get started.
        </div>
      )}

      <div className="tqa-settings-sections">

        {/* Business Details */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">Business Details</h3>
          <table className="form-table">
            <tbody>
              <Field label="Business Name" name="business_name" placeholder="Acme Plumbing Ltd" />
              <Field label="ABN / GST / Tax Number" name="tax_number" placeholder="123-456-789" />
              <tr>
                <th style={{ paddingTop: 14, verticalAlign: 'top' }}><label style={{ fontWeight: 600 }}>Business Logo</label></th>
                <td>
                  {form.business_logo && (
                    <div style={{ marginBottom: 8 }}>
                      <img src={form.business_logo} alt="Logo" style={{ maxHeight: 60, border: '1px solid #e5e7eb', borderRadius: 4 }} />
                    </div>
                  )}
                  <button type="button" className="tqa-btn tqa-btn-secondary tqa-btn-sm" onClick={openMediaUploader}>
                    {form.business_logo ? 'Change Logo' : 'Upload Logo'}
                  </button>
                  {form.business_logo && (
                    <button type="button" className="tqa-btn tqa-btn-sm" style={{ marginLeft: 8, color: '#e02424' }} onClick={() => set('business_logo', '')}>
                      Remove
                    </button>
                  )}
                  <p className="description" style={{ marginTop: 4 }}>Appears on PDF quotes and invoices.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {/* Tax & Pricing */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">Tax &amp; Pricing</h3>
          <table className="form-table">
            <tbody>
              <tr>
                <th style={{ width: 220 }}><label htmlFor="tqa-default_tax_rate" style={{ fontWeight: 600 }}>Default Tax Rate (%)</label></th>
                <td>
                  <input
                    id="tqa-default_tax_rate"
                    type="number"
                    className="tqa-input"
                    style={{ width: 100 }}
                    value={form.default_tax_rate || ''}
                    min="0" max="100" step="0.01"
                    onChange={e => set('default_tax_rate', e.target.value)}
                  />
                  <p className="description">e.g. 15 for NZ GST, 10 for AU GST, 20 for UK VAT</p>
                </td>
              </tr>
              <tr>
                <th><label htmlFor="tqa-currency_symbol" style={{ fontWeight: 600 }}>Currency Symbol</label></th>
                <td>
                  <input id="tqa-currency_symbol" type="text" className="tqa-input" style={{ width: 80 }}
                    value={form.currency_symbol || ''} placeholder="$"
                    onChange={e => set('currency_symbol', e.target.value)} />
                </td>
              </tr>
              <tr>
                <th><label htmlFor="tqa-currency_code" style={{ fontWeight: 600 }}>Currency Code</label></th>
                <td>
                  <input id="tqa-currency_code" type="text" className="tqa-input" style={{ width: 100 }}
                    value={form.currency_code || ''} placeholder="NZD"
                    onChange={e => set('currency_code', e.target.value)} />
                  <p className="description">e.g. NZD, AUD, GBP, USD</p>
                </td>
              </tr>
              <Field label="Quote Valid For (days)" name="quote_valid_days" type="number"
                help="Default expiry applied to new quotes." />
            </tbody>
          </table>
        </div>

        {/* Numbering */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">Document Numbering</h3>
          <table className="form-table">
            <tbody>
              <Field label="Quote Prefix" name="quote_prefix" placeholder="Q-"
                help='e.g. Q- produces Q-2026-0001' />
              <Field label="Invoice Prefix" name="invoice_prefix" placeholder="INV-"
                help='e.g. INV- produces INV-2026-0001' />
            </tbody>
          </table>
        </div>

        {/* Payment */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">Payment Details</h3>
          <table className="form-table">
            <tbody>
              <tr>
                <th style={{ paddingTop: 14, verticalAlign: 'top', width: 220 }}>
                  <label style={{ fontWeight: 600 }}>Default Payment Terms</label>
                </th>
                <td>
                  <textarea className="tqa-textarea" rows={2} style={{ maxWidth: 480 }}
                    value={form.payment_terms || ''}
                    onChange={e => set('payment_terms', e.target.value)} />
                </td>
              </tr>
              <tr>
                <th style={{ paddingTop: 14, verticalAlign: 'top' }}>
                  <label style={{ fontWeight: 600 }}>Bank Account Details</label>
                </th>
                <td>
                  <textarea className="tqa-textarea" rows={3} style={{ maxWidth: 480 }}
                    placeholder={'Bank: ANZ\nAccount: 01-1234-1234567-00'}
                    value={form.bank_details || ''}
                    onChange={e => set('bank_details', e.target.value)} />
                  <p className="description">Shown on invoices. Leave blank if using Stripe.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {/* Email */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">Email Settings</h3>
          <table className="form-table">
            <tbody>
              <Field label="From Name" name="email_from_name" placeholder="Acme Plumbing" />
              <Field label="From Email" name="email_from_address" type="email" placeholder="quotes@acmeplumbing.co.nz" />
              <Field label="Quote Email Subject" name="email_quote_subject"
                help='Use {business_name} and {quote_number} as placeholders.' />
              <Field label="Invoice Email Subject" name="email_invoice_subject"
                help='Use {business_name} and {invoice_number} as placeholders.' />
            </tbody>
          </table>
        </div>

        {/* Claude API Key */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">AI Settings (Claude API)</h3>
          <table className="form-table">
            <tbody>
              <tr>
                <th style={{ width: 220 }}><label style={{ fontWeight: 600 }}>Claude API Key</label></th>
                <td>
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                    <input
                      type="password"
                      className="tqa-input"
                      style={{ maxWidth: 320 }}
                      value={form.claude_api_key || ''}
                      placeholder={form.claude_api_key ? 'Key saved — enter new key to replace' : 'sk-ant-api03-…'}
                      onChange={e => { set('claude_api_key', e.target.value); setKeyValid(null); }}
                    />
                    <button type="button" className="tqa-btn tqa-btn-secondary tqa-btn-sm" onClick={testKey} disabled={testing}>
                      {testing ? 'Testing…' : 'Test Key'}
                    </button>
                    {keyValid === true  && <span style={{ color: '#059669', fontWeight: 600 }}>✓ Valid</span>}
                    {keyValid === false && <span style={{ color: '#e02424', fontWeight: 600 }}>✗ Invalid</span>}
                  </div>
                  <p className="description">
                    Get your key at <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>.
                    Leave blank to keep the existing key. You pay Anthropic directly — typical cost ~$0.01–0.05 per quote.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {/* License */}
        <div className="tqa-card">
          <h3 className="tqa-card-title">License</h3>
          <table className="form-table">
            <tbody>
              <Field label="License Key" name="license_key" placeholder="Enter Pro license key"
                help={<>Unlock unlimited quotes and Pro features. <a href="https://tradequoteai.com/#pricing" target="_blank" rel="noopener">Get a key →</a></>}
              />
            </tbody>
          </table>
        </div>

      </div>

      <div className="tqa-actions" style={{ marginTop: 24 }}>
        <button className="tqa-btn tqa-btn-primary tqa-btn-lg" onClick={save} disabled={saving}>
          {saving ? 'Saving…' : 'Save Settings'}
        </button>
      </div>
    </div>
  );
}
