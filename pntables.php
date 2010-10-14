<?php
// $Id$
//
// Mediashare by Jorn Wildt (C)
//

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in information about the tables that the module uses.
 */
function mediashare_pntables()
{
    $pntable = array();


    // Album and media setup
    $pntable['mediashare_albums'] = DBUtil::getLimitedTablename('mediashare_albums');

    $pntable['mediashare_albums_column'] = array(
        'id'             => 'ms_id',
        'ownerId'        => 'ms_ownerid',
        'createdDate'    => 'ms_createddate',
        'modifiedDate'   => 'ms_modifieddate',
        'title'          => 'ms_title',
        'keywords'       => 'ms_keywords',
        'summary'        => 'ms_summary',
        'description'    => 'ms_description',
        'template'       => 'ms_template',
        'parentAlbumId'  => 'ms_parentAlbumId',
        'access'         => 'ms_accesslevel',
        'viewKey'        => 'ms_viewkey',
        'mainMediaId'    => 'ms_mainmediaid',
        'thumbnailSize'  => 'ms_thumbnailsize',
        'nestedSetLeft'  => 'ms_nestedsetleft',
        'nestedSetRight' => 'ms_nestedsetright',
        'nestedSetLevel' => 'ms_nestedsetlevel',
        'extappURL'      => 'ms_extappURL',
        'extappData'     => 'ms_extappData'
    );
    $pntable['mediashare_albums_column_def'] = array(
        'id'             => 'I NOTNULL AUTO PRIMARY',
        'ownerId'        => 'I NOTNULL',
        'createdDate'    => 'T NOTNULL',
        'modifiedDate'   => 'T NOTNULL DEFTIMESTAMP',
        'title'          => "C(255) NOTNULL DEFAULT ''",
        'keywords'       => "C(255) NOTNULL DEFAULT ''",
        'summary'        => "X NOTNULL DEFAULT ''",
        'description'    => "X NOTNULL DEFAULT ''",
        'template'       => "C(255) NOTNULL DEFAULT 'msslideshow'",
        'parentAlbumId'  => 'I',
        'access'         => 'I1 NOTNULL DEFAULT 0',
        'viewKey'        => 'C(32) NOTNULL',
        'mainMediaId'    => 'I NOTNULL DEFAULT 0',
        'thumbnailSize'  => 'I NOTNULL',
        'nestedSetLeft'  => 'I NOTNULL DEFAULT 0',
        'nestedSetRight' => 'I NOTNULL DEFAULT 0',
        'nestedSetLevel' => 'I NOTNULL DEFAULT 0',
        'extappURL'      => 'C(255)',
        'extappData'     => 'C(512)'
    );


    // Media information
    $pntable['mediashare_media'] = DBUtil::getLimitedTablename('mediashare_media');

    $pntable['mediashare_media_column'] = array(
        'id'            => 'ms_id',
        'ownerId'       => 'ms_ownerid',
        'createdDate'   => 'ms_createddate',
        'modifiedDate'  => 'ms_modifieddate',
        'title'         => 'ms_title',
        'keywords'      => 'ms_keywords',
        'description'   => 'ms_description',
        'parentAlbumId' => 'ms_parentalbumid',
        'position'      => 'ms_position',
        'mediaHandler'  => 'ms_mediahandler',
        'thumbnailId'   => 'ms_thumbnailid',
        'previewId'     => 'ms_previewid',
        'originalId'    => 'ms_originalid'
    );
    $pntable['mediashare_media_column_def'] = array(
        'id'            => 'I NOTNULL AUTO PRIMARY',
        'ownerId'       => 'I NOTNULL',
        'createdDate'   => 'T NOTNULL',
        'modifiedDate'  => 'T NOTNULL DEFTIMESTAMP',
        'title'         => "C(255) NOTNULL DEFAULT ''",
        'keywords'      => "C(255) NOTNULL DEFAULT ''",
        'description'   => "X NOTNULL DEFAULT ''",
        'parentAlbumId' => 'I NOTNULL',
        'position'      => 'I NOTNULL',
        'mediaHandler'  => 'C(50) NOTNULL',
        'thumbnailId'   => 'I NOTNULL',
        'previewId'     => 'I NOTNULL',
        'originalId'    => 'I NOTNULL'
    );

 
    // Keyword handling
    $pntable['mediashare_keywords'] = DBUtil::getLimitedTablename('mediashare_keywords');

    $pntable['mediashare_keywords_column'] = array(
        'itemId'  => 'ms_itemid',
        'type'    => 'ms_type',
        'keyword' => 'ms_keyword'
    );
    $pntable['mediashare_keywords_column_def'] = array(
        'itemId'  => 'I NOTNULL',
        'type'    => 'C(5) NOTNULL',
        'keyword' => 'C(50) NOTNULL'
    );
    $pntable['mediashare_keywords_column_idx'] = array('keywordsKeywordIdx' => array('keyword'));


    // Media storage (image information)
    $pntable['mediashare_mediastore'] = DBUtil::getLimitedTablename('mediashare_mediastore');

    $pntable['mediashare_mediastore_column'] = array(
        'id'       => 'mss_id',
        'fileRef'  => 'mss_fileref',
        'mimeType' => 'mss_mimetype',
        'width'    => 'mss_width',
        'height'   => 'mss_height',
        'bytes'    => 'mss_bytes'
    );
    $pntable['mediashare_mediastore_column_def'] = array(
        'id'       => 'I NOTNULL AUTO PRIMARY',
        'fileRef'  => 'C(300) NOTNULL',
        'mimeType' => 'C(100) NOTNULL',
        'width'    => 'I2 NOTNULL',
        'height'   => 'I2 NOTNULL',
        'bytes'    => 'I NOTNULL'
    );


    // Media DB storage (image data for storing images in DB)
    $pntable['mediashare_mediadb'] = DBUtil::getLimitedTablename('mediashare_mediadb');

    $pntable['mediashare_mediadb_column'] = array(
        'id'      => 'mdb_id',
        'fileref' => 'mdb_ref',
        'mode'    => 'mdb_mode',
        'type'    => 'mdb_type',
        'bytes'   => 'mdb_bytes',
        'data'    => 'mdb_data'
    );
    $pntable['mediashare_mediadb_column_def'] = array(
        'id'      => 'I NOTNULL AUTO PRIMARY',
        'fileref' => 'C(50) NOTNULL',
        'mode'    => 'C(20) NOTNULL',
        'type'    => 'C(10) NOTNULL',
        'bytes'   => 'I NOTNULL',
        'data'    => 'B NOTNULL'
    );
 

    // Media handlers
    $pntable['mediashare_mediahandlers'] = DBUtil::getLimitedTablename('mediashare_mediahandlers');

    $pntable['mediashare_mediahandlers_column'] = array(
      'id'            => 'ms_id',
      'mimeType'      => 'ms_mimetype',
      'fileType'      => 'ms_filetype',
      'foundMimeType' => 'ms_foundmimetype',
      'foundFileType' => 'ms_foundfiletype',
      'handler'       => 'ms_handler',
      'title'         => 'ms_title',
      'active'        => 'ms_active'
    );
    $pntable['mediashare_mediahandlers_column_def'] = array(
      'id'            => 'I NOTNULL AUTO PRIMARY',
      'mimeType'      => 'C(50)',
      'fileType'      => 'C(10)',
      'foundMimeType' => 'C(50) NOTNULL',
      'foundFileType' => 'C(50) NOTNULL',
      'handler'       => 'C(50) NOTNULL',
      'title'         => "C(50) NOTNULL DEFAULT ''",
      'active'        => 'I1 NOTNULL DEFAULT 1'
    );


    // Sources
    $pntable['mediashare_sources'] = DBUtil::getLimitedTablename('mediashare_sources');

    $pntable['mediashare_sources_column'] = array(
        'id'          => 'ms_id',
        'name'        => 'ms_name',
        'title'       => 'ms_title',
        'formEncType' => 'ms_formenctype',
        'active'      => 'ms_active'
    );
    $pntable['mediashare_sources_column_def'] = array(
        'id'          => 'I NOTNULL AUTO PRIMARY',
        'name'        => 'C(50) NOTNULL',
        'title'       => "C(50) NOTNULL DEFAULT ''",
        'formEncType' => "C(50) NOTNULL DEFAULT 'multipart/form-data'",
        'active'      => 'I1 NOTNULL DEFAULT 1'
    );

 
    // Access table setup
    $pntable['mediashare_access'] = DBUtil::getLimitedTablename('mediashare_access');

    $pntable['mediashare_access_column'] = array(
        'id'       => 'msa_id',       // Unique ID
        'albumId'  => 'msa_albumid',  // album ID for which access applies
        'groupId'  => 'msa_groupid',  // (user) group ID for which access applies
        'access'   => 'msa_access'    // access type
    );
    $pntable['mediashare_access_column_def'] = array(
        'id'       => 'I NOTNULL AUTO PRIMARY',
        'albumId'  => 'I NOTNULL',
        'groupId'  => 'I NOTNULL',
        'access'   => 'I NOTNULL'
    );
    $pntable['mediashare_access_column_idx'] = array('accessAlbumIdx' => array('albumId'));


    // Setup table setup
    $pntable['mediashare_setup'] = DBUtil::getLimitedTablename('mediashare_setup');

    $pntable['mediashare_setup_column'] = array(
        'id'            => 'ms_id',           // Unique ID
        'kind'          => 'ms_kind',         // 0: group, 1: user
        'storageLimit'  => 'ms_storagelimit', // Storage limit
        'unitId'        => 'ms_unitid'        // User or group id this applies to
    );
    $pntable['mediashare_setup_column_def'] = array(
        'id'            => 'I NOTNULL AUTO PRIMARY',
        'kind'          => 'I1 NOTNULL',
        'storageLimit'  => 'I NOTNULL',
        'unitId'        => 'I NOTNULL'
    );


    // Invitations table
    $pntable['mediashare_invitation'] = DBUtil::getLimitedTablename('mediashare_invitation');

    $pntable['mediashare_invitation_column'] = array(
        'id'         => 'msinv_id',
        'created'    => 'msinv_created',
        'albumId'    => 'msinv_albumid',
        'key'        => 'msinv_key',
        'viewCount'  => 'msinv_count',
        'email'      => 'msinv_email',
        'subject'    => 'msinv_subject',
        'text'       => 'msinv_text',
        'sender'     => 'msinv_sender',
        'expires'    => 'msinv_expires'
    );
    $pntable['mediashare_invitation_column_def'] = array(
        'id'         => 'I NOTNULL AUTO PRIMARY',
        'created'    => 'T NOTNULL',
        'albumId'    => 'I NOTNULL',
        'key'        => "C(20) NOTNULL DEFAULT ''",
        'viewCount'  => 'I NOTNULL DEFAULT 0',
        'email'      => "C(100) NOTNULL DEFAULT ''",
        'subject'    => "C(255) NOTNULL DEFAULT ''",
        'text'       => "X NOTNULL DEFAULT ''",
        'sender'     => "C(50) NOTNULL DEFAULT ''",
        'expires'    => 'T'
    );
 

    // Photoshare upgrade table (deprecated)
    $pntable['mediashare_photoshare'] = DBUtil::getLimitedTablename('mediashare_photoshare');


    return $pntable;
}
