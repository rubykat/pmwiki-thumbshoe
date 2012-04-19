<?php if (!defined('PmWiki')) exit();
/*
    Based on "delete.php" by
    Patrick R. Michaud (pmichaud@pobox.com)

    This is different from the original delete action in that
    it enables deletion from any PageStore which is writeable,
    not just the default WikiDir PageStore.  This will delete the
    first version of a page that it finds.

    This script provides ?action=delete as an alternate method for
    removing pages from the wiki.  The delete action is controlled
    by a separate delete password (set via ?action=attr) that can
    be set on pages and groups.  To require a different set of
    privileges (e.g, 'admin'), try

        $HandleAuth['delete'] = 'admin';

    In addition, the script disables the $DeleteKeyPattern form of
    deleting, so that the only mechanism for deleting a page is to
    use ?action=delete.  To restore this, simply use

        $DeleteKeyPattern = '/^\\s*delete\\s*$/s';

*/

# disable deletion via ?action=edit
SDV($DeleteKeyPattern, '.^');

# add "delete" password to page attributes
SDV($PageAttributes['passwddelete'], '$[Set new delete password:]');

# set default password for delete action
SDV($DefaultPasswords['delete'], '');

# add "?action=delete"
SDV($HandleActions['delete'], 'ThumbShoeHandleDelete');
SDV($HandleAuth['delete'], 'delete');
SDV($AuthCascade['delete'], 'edit');

function ThumbShoeHandleDelete($pagename, $auth='delete') {
    global $WikiLibDirs, $WikiDir, $LastModFile;
    $page = RetrieveAuthPage($pagename, $auth, true, READPAGE_CURRENT);
    if (!$page) { Abort("?cannot delete $pagename"); return; }
    $deleted = false;
    foreach((array)$WikiLibDirs as $dir)
    {
        if ($dir->exists($pagename) and $dir->iswrite)
        {
            $dir->delete($pagename);
            $deleted = true;
            break;
        }
    }
    if (!$deleted)
    {
        // look in the default WikiDir
        if ($WikiDir->exists($pagename))
        {
            $WikiDir->delete($pagename);
            $deleted = true;
        }
    }
    if ($deleted && $LastModFile)
    {
        touch($LastModFile);
        fixperms($LastModFile);
    }
    Redirect($pagename);
    exit;
}

