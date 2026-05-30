import { useState, useEffect } from '@wordpress/element';
import { api } from './api';
import JobTracker from './JobTracker';

const { adminUrl, freeQuota, currency, isPro: initialIsPro } = window.tqaData || {};

export default function Dashboard() {
  const [stats, setStats] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api.getStats()
      .then(setStats)
      .catch(e => setError(e.message));
  }, []);

  const sym = currency?.symbol || '$';
  const isPro = stats?.is_pro ?? initialIsPro;

  const fmt = (n) => sym + (parseFloat(n || 0)).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  return (
    <div className="tqa-dashboard">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      <div className="tqa-stats-grid">
        <StatCard label="Quotes This Month"      value={stats?.quotes_this_month ?? '–'}          icon="📋" />
        <StatCard label="Acceptance Rate"         value={stats ? stats.acceptance_rate + '%' : '–'} icon="✅" />
        <StatCard label="Outstanding Invoices"    value={stats ? fmt(stats.outstanding_invoices) : '–'} icon="💰" />
        {isPro ? (
          <StatCard label="Plan" value="Pro ✨" icon="⭐" sub="Unlimited quotes" color="#7c3aed" />
        ) : (
          <StatCard
            label="Free Quota"
            value={stats ? `${stats.free_quota_remaining} / ${freeQuota}` : '–'}
            icon="🎁"
            sub={<a href="https://tradequoteai.com/#pricing" target="_blank" rel="noopener">Upgrade to Pro →</a>}
            color={stats?.free_quota_remaining === 0 ? '#ef4444' : undefined}
          />
        )}
      </div>

      <div className="tqa-dashboard-actions">
        <a href={adminUrl + '?page=tqa-new-quote'}  className="tqa-btn tqa-btn-primary tqa-btn-lg">✨ Create New Quote</a>
        <a href={adminUrl + '?page=tqa-quotes'}     className="tqa-btn tqa-btn-secondary">All Quotes</a>
        <a href={adminUrl + '?page=tqa-invoices'}   className="tqa-btn tqa-btn-secondary">Invoices</a>
        <a href={adminUrl + '?page=tqa-customers'}  className="tqa-btn tqa-btn-secondary">Customers</a>
      </div>

      <div style={{ marginTop: 32 }}>
        <h2 style={{ fontSize: 18, marginBottom: 12, fontWeight: 700 }}>Job Tracker</h2>
        <JobTracker />
      </div>
    </div>
  );
}

function StatCard({ label, value, icon, sub, color }) {
  return (
    <div className="tqa-stat-card">
      <div className="tqa-stat-icon">{icon}</div>
      <div className="tqa-stat-value" style={color ? { color } : {}}>{value}</div>
      <div className="tqa-stat-label">{label}</div>
      {sub && <div className="tqa-stat-sub">{sub}</div>}
    </div>
  );
}
