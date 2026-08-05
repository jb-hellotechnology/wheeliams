<?php

/**
 * Order email templates (Phase 3).
 *
 * Each template has to / bcc / subject / content, all of which may contain
 * {PLACEHOLDERS} that are resolved when an order email is generated. The
 * attachments field describes which Google Drive files to attach.
 */
class Wheeliams_Email_Templates extends PerchAPI_Factory
{
    protected $table = 'wheeliams_email_templates';
    protected $pk    = 'perch3_wheeliams_email_templateID';
    protected $singular_classname = 'Wheeliams_Email_Template';

    protected $tbl = 'perch3_wheeliams_email_templates';

    public function install()
    {
        $this->db->execute("CREATE TABLE IF NOT EXISTS ".$this->tbl." (
            perch3_wheeliams_email_templateID INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) DEFAULT NULL,
            email_to VARCHAR(255) DEFAULT NULL,
            email_bcc VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            content TEXT,
            attachments TEXT,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (perch3_wheeliams_email_templateID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* Signature-compatible override of PerchFactory::all() that returns plain
       rows (arrays), not hydrated objects — that's what the callers expect.
       templates() is kept as an alias so either name is safe. */
    public function all($Paging = false)
    {
        return $this->db->get_rows('SELECT * FROM '.$this->tbl.' ORDER BY name ASC');
    }

    public function templates()
    {
        return $this->all();
    }

    public function get($id)
    {
        return $this->db->get_row('SELECT * FROM '.$this->tbl.' WHERE perch3_wheeliams_email_templateID='.$this->db->pdb((int)$id));
    }

    public function remove($id)
    {
        $this->db->delete($this->tbl, 'perch3_wheeliams_email_templateID', (int)$id);
    }

    /* Insert or update from a submitted-form data array. Returns the row id. */
    public function save($data, $id = 0)
    {
        $row = array(
            'name'        => $data['name']        ?? '',
            'email_to'    => $data['email_to']    ?? '',
            'email_bcc'   => $data['email_bcc']   ?? '',
            'subject'     => $data['subject']     ?? '',
            'content'     => $data['content']     ?? '',
            'attachments' => $data['attachments'] ?? '',
        );

        if($id){
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update($this->tbl, $row, 'perch3_wheeliams_email_templateID', (int)$id);
            return (int)$id;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl, $row);
        return 0;
    }
}
