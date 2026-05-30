const { restUrl, nonce } = window.tqaData || {};

async function request(path, options = {}) {
  const res = await fetch(restUrl + path, {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...options.headers,
    },
    ...options,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || data.message || 'Request failed');
  return data;
}

export const api = {
  get:    (path)         => request(path),
  post:   (path, body)   => request(path, { method: 'POST',   body: JSON.stringify(body) }),
  put:    (path, body)   => request(path, { method: 'PUT',    body: JSON.stringify(body) }),
  delete: (path)         => request(path, { method: 'DELETE' }),

  generateQuote:    (desc)        => api.post('ai/generate', { job_description: desc }),
  getQuotes:        (params = {}) => api.get('quotes?' + new URLSearchParams(params)),
  createQuote:      (data)        => api.post('quotes', data),
  updateQuote:      (id, data)    => api.put(`quotes/${id}`, data),
  deleteQuote:      (id)          => api.delete(`quotes/${id}`),
  sendQuote:        (id, data)    => api.post(`quotes/${id}/send`, data),
  convertToInvoice: (id, data)    => api.post(`quotes/${id}/convert`, data),

  getInvoices:   (params = {}) => api.get('invoices?' + new URLSearchParams(params)),
  createInvoice: (data)        => api.post('invoices', data),
  updateInvoice: (id, data)    => api.put(`invoices/${id}`, data),
  deleteInvoice: (id)          => api.delete(`invoices/${id}`),
  sendInvoice:   (id, data)    => api.post(`invoices/${id}/send`, data),
  markPaid:      (id)          => api.post(`invoices/${id}/mark-paid`, {}),

  getCustomers:   (params = {}) => api.get('customers?' + new URLSearchParams(params)),
  createCustomer: (data)        => api.post('customers', data),
  updateCustomer: (id, data)    => api.put(`customers/${id}`, data),
  deleteCustomer: (id)          => api.delete(`customers/${id}`),

  getStats:    () => api.get('stats'),
  getSettings: () => api.get('settings'),
  saveSettings:(data) => api.post('settings', data),
};
