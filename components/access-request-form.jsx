'use client';

import { useState } from 'react';

const ROLES = [
  'Founder / CEO',
  'Investor / VC',
  'Operator / C-level',
  'Product / Design',
  'Creative Director',
  'Journalist / Media',
  'Other',
];

const INITIAL_FORM = {
  name: '',
  email: '',
  role: '',
  company: '',
  linkedin: '',
  reason: '',
};

export default function AccessRequestForm() {
  const [form, setForm] = useState(INITIAL_FORM);
  const [mailOpened, setMailOpened] = useState(false);

  function handleChange(event) {
    const { name, value } = event.target;

    setForm((current) => ({
      ...current,
      [name]: value,
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();

    const subject = 'REVELATIONS Inner Circle request';

    const body = [
      `Full name: ${form.name}`,
      `Email: ${form.email}`,
      `Role: ${form.role}`,
      `Company / Project: ${form.company || 'Not provided'}`,
      `LinkedIn / Website: ${form.linkedin || 'Not provided'}`,
      '',
      'Why I belong here:',
      form.reason,
    ].join('\n');

    const mailto =
      `mailto:info@julscorp.com` +
      `?subject=${encodeURIComponent(subject)}` +
      `&body=${encodeURIComponent(body)}`;

    setMailOpened(true);
    window.location.href = mailto;
  }

  return (
    <div>
      {mailOpened && (
        <div className="mb-8 border border-rose/30 bg-rose/5 px-5 py-4">
          <p className="font-body text-sm leading-relaxed text-muted-foreground">
            Your email application has been opened. Send the prepared message
            there to complete your request.
          </p>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label
              htmlFor="access-name"
              className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
            >
              Full Name *
            </label>

            <input
              id="access-name"
              name="name"
              required
              value={form.name}
              onChange={handleChange}
              className="w-full border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
              placeholder="Your name"
            />
          </div>

          <div>
            <label
              htmlFor="access-email"
              className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
            >
              Email *
            </label>

            <input
              id="access-email"
              name="email"
              type="email"
              required
              value={form.email}
              onChange={handleChange}
              className="w-full border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
              placeholder="you@company.com"
            />
          </div>
        </div>

        <div>
          <label
            htmlFor="access-role"
            className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
          >
            Who are you? *
          </label>

          <select
            id="access-role"
            name="role"
            required
            value={form.role}
            onChange={handleChange}
            className="w-full border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
          >
            <option value="">Select your role…</option>

            {ROLES.map((role) => (
              <option key={role} value={role}>
                {role}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label
            htmlFor="access-company"
            className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
          >
            Company / Project
          </label>

          <input
            id="access-company"
            name="company"
            value={form.company}
            onChange={handleChange}
            className="w-full border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
            placeholder="Where do you operate?"
          />
        </div>

        <div>
          <label
            htmlFor="access-linkedin"
            className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
          >
            LinkedIn or website
          </label>

          <input
            id="access-linkedin"
            name="linkedin"
            type="url"
            value={form.linkedin}
            onChange={handleChange}
            className="w-full border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
            placeholder="https://…"
          />
        </div>

        <div>
          <label
            htmlFor="access-reason"
            className="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground"
          >
            Why do you belong here? *
          </label>

          <textarea
            id="access-reason"
            name="reason"
            required
            rows={4}
            value={form.reason}
            onChange={handleChange}
            className="w-full resize-none border border-border/40 bg-card px-4 py-3 font-body text-sm text-foreground outline-none transition-colors focus:border-rose/50"
            placeholder="What you build, what you see, why this matters to you…"
          />
        </div>

        <button
          type="submit"
          className="w-full border border-rose/40 py-4 font-mono text-xs uppercase tracking-[0.2em] text-foreground transition-colors duration-500 hover:bg-rose/10"
        >
          Prepare Request
        </button>

        <p className="text-center font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground">
          The request opens in your email app. No spam. No noise.
        </p>
      </form>
    </div>
  );
}
