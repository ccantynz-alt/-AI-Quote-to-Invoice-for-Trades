import { createRoot } from '@wordpress/element';
import Dashboard     from './components/Dashboard';
import QuoteBuilder  from './components/QuoteBuilder';
import QuotesList    from './components/QuotesList';
import InvoicesList  from './components/InvoicesList';
import CustomersList from './components/CustomersList';
import SettingsPage  from './components/SettingsPage';

const mount = (id, Component, props = {}) => {
  const el = document.getElementById(id);
  if (el) createRoot(el).render(<Component {...props} />);
};

mount('tqa-dashboard-root',  Dashboard);
mount('tqa-new-quote-root',  QuoteBuilder);
mount('tqa-quotes-root',     QuotesList);
mount('tqa-invoices-root',   InvoicesList);
mount('tqa-customers-root',  CustomersList);
mount('tqa-settings-root',   SettingsPage);
