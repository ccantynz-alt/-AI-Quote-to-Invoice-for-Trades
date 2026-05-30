import { useState, useEffect } from '@wordpress/element';
import { api } from './api';
import JobTracker from './JobTracker';

const { adminUrl, freeQuota } = window.tqaData || {};

export default function Dashboard() {
  const [stats, setStats]   = useState(null);
  const [error, setError]   = useState('');

  useEffect(() => {
    api.getStats()
      .then(setStats)
      .catch(e => setError(e.message));
  }, []);

  const fmt = (n) => new Intl.NumberFormat('en-NZ', { style: 'currency', currency: 'NZD' }).format(n || 0);

  return (
    <div className="tqa-dashboard">
      {error && <div className="tqa-alert tqa-alert-error">{error}</div>}

      <div className="tqa-stats-grid">
        <StatCard label="Quotes This Month" value={stats?.quotes_this_month ?? '–'} icon="📋" />
        <StatCard label="Acceptance Rate"   value={stats ? stats.acceptance_rate + '%' : '–'} icon="✅" />
        <StatCard label="Outstanding Invoices" value={stats ? fmt(stats.outstanding_invoices) : '–'} icon="💰" />
        <StatCard
          label="Free Quota Remaining"
          value={stats ? `${stats.free_quota_remaining} / ${freeQuota}` : '–'}
          icon="🎁"
          sub="quotes this month"
        />
      </div>

      <div className="tqa-dashboard-actions">
        <a href={adminUrl + '?page=tqa-new-quote'} className="tqa-btn tqa-btn-primary tqa-btn-lg">
          ✨ Create New Quote
        </a>
        <a href={adminUrl + '?page=tqa-quotes'}  className="tqa-btn tqa-btn-secondary">View All Quotes</a>
        <a href={adminUrl + '?page=tqa-invoices'} className="tqa-btn tqa-btn-secondary">View Invoices</a>
      </div>

      <div style={{ marginTop: 32 }}>
        <h2 style={{ fontSize: 18, marginBottom: 12 }}>Job Tracker</h2>
        <JobTracker />
      </div>
    </div>
  );
}

function StatCard({ label, value, icon, sub }) {
  return (
    <div className="tqa-stat-card">
      <div className="tqa-stat-icon">{icon}</div>
      <div className="tqa-stat-value">{value}</div>
      <div className="tqa-stat-label">{label}</div>
      {sub && <div className="tqa-stat-sub">{sub}</div>}
    </div>
  );
}
