<?php

/**
 * Sends a purchase-order / enquiry email to a supplier via the Brevo
 * transactional API, with the ordered parts' Drive files attached.
 *
 * Requires PhpSpreadsheet's cousins? No — needs Guzzle (present via google/apiclient)
 * and the Google Drive client. The consuming page must load vendor/autoload.php.
 *
 * Config (define in secrets.php):
 *   WHEELIAMS_BREVO_API_KEY   – Brevo v3 API key
 *   WHEELIAMS_ORDER_FROM_EMAIL, WHEELIAMS_ORDER_FROM_NAME – sender identity
 * Google Drive uses the existing CLIENT_ID / CLIENT_SECRET / REFRESH_TOKEN /
 * DRIVE_ROOT_FOLDER_ID constants already in secrets.php.
 */
class Wheeliams_Order_Email
{
    // File types worth sending to a supplier (drawings / STEP / DXF etc.).
    protected $attachExtensions = array('pdf', 'dwg', 'dxf', 'step', 'stp', 'iges', 'igs');

    // Brevo caps total message size; skip attachments beyond a sane budget.
    protected $maxAttachmentBytes = 9000000; // ~9 MB

    /* Send the order email for a saved PO. Returns ['ok'=>bool, 'msg'=>string]. */
    public function send($po, $lines, $template)
    {
        if(!defined('WHEELIAMS_BREVO_API_KEY') || WHEELIAMS_BREVO_API_KEY === ''){
            return array('ok' => false, 'msg' => 'Brevo API key not configured (set WHEELIAMS_BREVO_API_KEY in secrets.php).');
        }

        // Recipient = the supplier's ORDERING contact. Required.
        $Suppliers = new Wheeliams_Suppliers();
        $ordering  = $po['supplierID'] ? $Suppliers->orderingContact($po['supplierID']) : null;
        $orderingEmail = $ordering ? trim($ordering['email']) : '';
        if(!filter_var($orderingEmail, FILTER_VALIDATE_EMAIL)){
            return array(
                'ok'          => false,
                'no_ordering' => true,
                'msg'         => 'This supplier has no ORDERING contact with a valid email. Add one to the supplier before sending.',
            );
        }
        $orderingName = trim(($ordering['first_name'] ?? '').' '.($ordering['last_name'] ?? '')) ?: $po['supplierName'];

        // {SUPPLIER_EMAIL} resolves to the ORDERING contact.
        $vars = $this->vars($po, $lines, $orderingEmail);

        $to = trim(wheeliams_render_placeholders($template['email_to'], $vars));
        if(!filter_var($to, FILTER_VALIDATE_EMAIL)) $to = $orderingEmail; // fall back to the ordering contact
        $bcc     = trim(wheeliams_render_placeholders($template['email_bcc'], $vars));
        $subject = wheeliams_render_placeholders($template['subject'], $vars);
        $html    = wheeliams_render_placeholders($template['content'], $vars);

        $attachments = $this->attachments($lines);

        return $this->brevo($to, $orderingName, $bcc, $subject, $html, $attachments);
    }

    /* Placeholder values for a saved PO. */
    protected function vars($po, $lines, $supplierEmail)
    {
        $isPO = empty($po['is_enquiry']);
        return array(
            'SUPPLIER_NAME'   => $po['supplierName'],
            'SUPPLIER_EMAIL'  => $supplierEmail,
            'ORDER_TYPE'      => $isPO ? 'Purchase Order' : 'Enquiry',
            'ORDER_NUMBER'    => $po['number'],
            'ORDER_REFERENCE' => $po['reference'],
            'DATE'            => date('d/m/Y'),
            'COMPONENT_TYPE'  => $po['component_type'],
            'PROCESS_TYPE'    => $po['process_type'],
            'ORDER_TABLE'     => $this->orderTable($lines),
        );
    }

    protected function orderTable($lines)
    {
        $html  = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
        $html .= '<thead><tr><th>Part Code</th><th>Description</th><th>Order Qty</th><th>UOM</th><th>Note</th></tr></thead><tbody>';
        foreach($lines as $l){
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars($l['partCode']).'</td>';
            $html .= '<td>'.htmlspecialchars($l['description']).'</td>';
            $html .= '<td>'.wheeliams_num($l['order_qty']).'</td>';
            $html .= '<td>'.htmlspecialchars($l['uom']).'</td>';
            $html .= '<td>'.htmlspecialchars($l['supplier_note']).'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /* ---- Google Drive: gather & download the parts' files as Brevo attachments ---- */

    protected function driveService()
    {
        $client = new Google\Client();
        $client->setClientId(CLIENT_ID);
        $client->setClientSecret(CLIENT_SECRET);
        $client->setAccessType('offline');
        $client->addScope(Google\Service\Drive::DRIVE);
        $client->fetchAccessTokenWithRefreshToken(REFRESH_TOKEN);
        return new Google\Service\Drive($client);
    }

    protected function findFolder($drive, $name, $parentId)
    {
        $escaped = str_replace("'", "\\'", $name);
        $res = $drive->files->listFiles(array(
            'q'      => "mimeType='application/vnd.google-apps.folder' and name='{$escaped}' and '{$parentId}' in parents and trashed=false",
            'fields' => 'files(id)',
        ));
        $f = $res->getFiles();
        return count($f) ? $f[0]->getId() : null;
    }

    protected function listFilesRecursive($drive, $folderId)
    {
        $out = array();
        $pageToken = null;
        do {
            $params = array(
                'q'      => "'{$folderId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
                'fields' => 'nextPageToken, files(id, name, size)',
            );
            if($pageToken) $params['pageToken'] = $pageToken;
            $res = $drive->files->listFiles($params);
            foreach($res->getFiles() as $f){
                $out[] = array('id' => $f->getId(), 'name' => $f->getName(), 'size' => (int)$f->getSize());
            }
            $pageToken = $res->getNextPageToken();
        } while($pageToken);

        $subs = array();
        $pageToken = null;
        do {
            $params = array(
                'q'      => "'{$folderId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed=false",
                'fields' => 'nextPageToken, files(id)',
            );
            if($pageToken) $params['pageToken'] = $pageToken;
            $res = $drive->files->listFiles($params);
            $subs = array_merge($subs, $res->getFiles());
            $pageToken = $res->getNextPageToken();
        } while($pageToken);

        foreach($subs as $sub){
            $out = array_merge($out, $this->listFilesRecursive($drive, $sub->getId()));
        }
        return $out;
    }

    /* Build Brevo attachment list [{name, content(base64)}] for the PO's parts. */
    protected function attachments($lines)
    {
        $attachments = array();
        try {
            $drive = $this->driveService();
        } catch (Exception $e) {
            return $attachments; // no Drive → send without attachments rather than fail
        }

        $budget = $this->maxAttachmentBytes;
        $seen   = array();

        foreach($lines as $l){
            $code = $l['partCode'];
            $type = wheeliams_drive_type_for($code, $l['type']);
            if(isset($seen[$code.'|'.$type])) continue;
            $seen[$code.'|'.$type] = true;

            try {
                $typeFolder = $this->findFolder($drive, $type, DRIVE_ROOT_FOLDER_ID);
                if(!$typeFolder) continue;
                $partFolder = $this->findFolder($drive, $code, $typeFolder);
                if(!$partFolder) continue;

                foreach($this->listFilesRecursive($drive, $partFolder) as $file){
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if(!in_array($ext, $this->attachExtensions, true)) continue;
                    if($file['size'] > 0 && $file['size'] > $budget) continue; // too big alone

                    $content = $drive->files->get($file['id'], array('alt' => 'media'))->getBody()->getContents();
                    $budget -= strlen($content);
                    if($budget < 0) break 2; // hit the size budget

                    $attachments[] = array('name' => $file['name'], 'content' => base64_encode($content));
                }
            } catch (Exception $e) {
                continue; // skip a part whose files can't be read
            }
        }
        return $attachments;
    }

    /* ---- Brevo transactional send via the API ---- */

    protected function brevo($toEmail, $toName, $bcc, $subject, $html, $attachments)
    {
        $fromEmail = defined('WHEELIAMS_ORDER_FROM_EMAIL') ? WHEELIAMS_ORDER_FROM_EMAIL
                   : (defined('PERCH_EMAIL_FROM') ? PERCH_EMAIL_FROM : '');
        $fromName  = defined('WHEELIAMS_ORDER_FROM_NAME') ? WHEELIAMS_ORDER_FROM_NAME
                   : (defined('PERCH_EMAIL_FROM_NAME') ? PERCH_EMAIL_FROM_NAME : 'Wheeliams');
        if(!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)){
            return array('ok' => false, 'msg' => 'Sender email not configured (set WHEELIAMS_ORDER_FROM_EMAIL in secrets.php).');
        }

        $payload = array(
            'sender'      => array('name' => $fromName, 'email' => $fromEmail),
            'to'          => array(array('email' => $toEmail, 'name' => $toName)),
            'subject'     => $subject,
            'htmlContent' => $html,
        );
        if(filter_var($bcc, FILTER_VALIDATE_EMAIL)){
            $payload['bcc'] = array(array('email' => $bcc));
        }
        if($attachments){
            $payload['attachment'] = $attachments;
        }

        try {
            $client = new GuzzleHttp\Client();
            $resp = $client->post('https://api.brevo.com/v3/smtp/email', array(
                'headers' => array(
                    'api-key'      => WHEELIAMS_BREVO_API_KEY,
                    'accept'       => 'application/json',
                    'content-type' => 'application/json',
                ),
                'json'        => $payload,
                'http_errors' => false,
                'timeout'     => 30,
            ));
            $code = $resp->getStatusCode();
            if($code >= 200 && $code < 300){
                return array('ok' => true, 'to' => $toEmail, 'msg' => 'Sent to '.$toEmail.($attachments ? ' with '.count($attachments).' attachment(s).' : ''));
            }
            return array('ok' => false, 'msg' => 'Brevo error ('.$code.'): '.substr((string)$resp->getBody(), 0, 300));
        } catch (Exception $e) {
            return array('ok' => false, 'msg' => 'Send failed: '.$e->getMessage());
        }
    }
}
