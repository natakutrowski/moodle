/** Human review gate; documentation safeguard, not a trust/authorization mechanism. */
import {readFileSync} from 'node:fs';
let approval;
try { approval = JSON.parse(readFileSync('campusfr-approval.json','utf8')); }
catch { throw new Error('STOP : campusfr-approval.json absent. Ne pas packager le brouillon.'); }
for (const field of ['commerceIntegrated','legalIntegrated','mediaIntegrated','translationsReviewed']) {
  if (approval[field] !== true) throw new Error(`STOP : ${field} doit être validé par une personne.`);
}
if (typeof approval.approvedBy !== 'string' || approval.approvedBy.trim().length < 4 ||
    !/^\d{4}-\d{2}-\d{2}$/.test(String(approval.reviewedAt))) {
  throw new Error('STOP : approbateur et date de validation obligatoires.');
}
console.log('Revue humaine déclarée :', approval.approvedBy, approval.reviewedAt);
