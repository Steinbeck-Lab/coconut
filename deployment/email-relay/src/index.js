import PostalMime from 'postal-mime';

// workflow_dispatch inputs share a 65,535 character limit.
const MAX_BODY_LENGTH = 50000;

const QUOTE_MARKERS = [
  /^(On|Am)\s[\s\S]{0,300}?(wrote|schrieb):\s*$/m,
  /^-{2,}\s*(Original Message|Ursprüngliche Nachricht)\s*-{2,}/im,
  /^_{10,}\s*$/m,
  /^From:\s.+$\n^(Sent|Date):\s/im,
  /^Von:\s.+$\n^(Gesendet|Datum):\s/im,
];

function stripQuotedReply(text) {
  let cut = text.length;
  for (const marker of QUOTE_MARKERS) {
    const match = marker.exec(text);
    if (match && match.index < cut) cut = match.index;
  }
  return text
    .slice(0, cut)
    .split('\n')
    .filter((line) => !line.startsWith('>'))
    .join('\n')
    .trim();
}

function htmlToText(html) {
  return html
    .replace(/<(style|script)[\s\S]*?<\/\1>/gi, '')
    .replace(/<br\s*\/?>|<\/(p|div|li|tr|h\d)>/gi, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/\n{3,}/g, '\n\n');
}

function isAllowedSender(address, allowList) {
  if (!allowList) return true;
  const sender = address.toLowerCase();
  return allowList
    .split(',')
    .map((entry) => entry.trim().toLowerCase())
    .filter(Boolean)
    .some((entry) => (entry.startsWith('@') ? sender.endsWith(entry) : sender === entry));
}

export default {
  async email(message, env) {
    if (!isAllowedSender(message.from, env.ALLOWED_SENDERS)) {
      message.setReject('Sender not allowed');
      return;
    }

    const email = await PostalMime.parse(message.raw);
    const text = email.text || htmlToText(email.html || '');

    const response = await fetch(
      `https://api.github.com/repos/${env.GITHUB_REPO}/actions/workflows/${env.WORKFLOW_FILE}/dispatches`,
      {
        method: 'POST',
        headers: {
          Accept: 'application/vnd.github+json',
          Authorization: `Bearer ${env.GITHUB_TOKEN}`,
          'Content-Type': 'application/json',
          'User-Agent': 'coconut-email-relay',
          'X-GitHub-Api-Version': '2022-11-28',
        },
        body: JSON.stringify({
          ref: env.WORKFLOW_REF,
          inputs: {
            subject: email.subject || '',
            from_name: email.from?.name || '',
            from_email: email.from?.address || message.from,
            body: stripQuotedReply(text).slice(0, MAX_BODY_LENGTH),
          },
        }),
      }
    );

    if (!response.ok) {
      throw new Error(`GitHub workflow dispatch failed: ${response.status} ${await response.text()}`);
    }
  },
};
