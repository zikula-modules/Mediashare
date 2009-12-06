<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in information about the tables that the module uses.
 */
function mediashare_pntables()
{
  $pntable = array();
  $prefix = pnConfigGetVar('prefix');

  // Album and media setup
  $tableName = $prefix . '_mediashare_albums';

  $pntable['mediashare_albums'] = $tableName;

  $pntable['mediashare_albums_column'] = array('id'             => 'ms_id',
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
                                               'extappData'     => 'ms_extappData');

  $tableName = $prefix . '_mediashare_media';

  $pntable['mediashare_media'] = $tableName;

  $pntable['mediashare_media_column'] = array('id'            => 'ms_id',
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
                                              'originalId'    => 'ms_originalid');


    // Keyword handling

  $tableName = $prefix . '_mediashare_keywords';

  $pntable['mediashare_keywords'] = $tableName;

  $pntable['mediashare_keywords_column'] = array('itemId'  => 'ms_itemid',
                                                 'type'    => 'ms_type',
                                                 'keyword' => 'ms_keyword');


    // Media storage (image information)

  $tableName = $prefix . '_mediashare_mediastore';

  $pntable['mediashare_mediastore'] = $tableName;

  $pntable['mediashare_mediastore_column'] = array('id'       => 'mss_id',
                                                   'fileRef'  => 'mss_fileref',
                                                   'mimeType' => 'mss_mimetype',
                                                   'width'    => 'mss_width',
                                                   'height'   => 'mss_height',
                                                   'bytes'    => 'mss_bytes',
                                                   'data'     => 'mss_data'); // Unused!!


    // Media DB storage (image data for storing images in DB)

  $tableName = $prefix . '_mediashare_mediadb';

  $pntable['mediashare_mediadb'] = $tableName;

  $pntable['mediashare_mediadb_column'] = array('id'      => 'mdb_id',
                                                'fileref' => 'mdb_ref',
                                                'mode'    => 'mdb_mode',
                                                'type'    => 'mdb_type',
                                                'bytes'   => 'mdb_bytes',
                                                'data'    => 'mdb_data');


    // Media handlers

  $tableName = $prefix . '_mediashare_mediahandlers';

  $pntable['mediashare_mediahandlers'] = $tableName;

  $pntable['mediashare_mediahandlers_column'] = array('id'            => 'ms_id',
                                                      'mimeType'      => 'ms_mimetype',
                                                      'fileType'      => 'ms_filetype',
                                                      'foundMimeType' => 'ms_foundmimetype',
                                                      'foundFileType' => 'ms_foundfiletype',
                                                      'handler'       => 'ms_handler',
                                                      'title'         => 'ms_title',
                                                      'active'        => 'ms_active');

    // Sources

  $tableName = $prefix . '_mediashare_sources';

  $pntable['mediashare_sources'] = $tableName;

  $pntable['mediashare_sources_column'] = array('id'          => 'ms_id',
                                                'name'        => 'ms_name',
                                                'title'       => 'ms_title',
                                                'formEncType' => 'ms_formenctype',
                                                'active'      => 'ms_active');

    // Access table setup

  $tableName = $prefix . '_mediashare_access';

  $pntable['mediashare_access'] = $tableName;

  $pntable['mediashare_access_column'] = array('id'       => 'msa_id',       // Unique ID
                                               'albumId'  => 'msa_albumid',  // album ID for which access applies
                                               'groupId'  => 'msa_groupid',  // (user) group ID for which access applies
                                               'access'   => 'msa_access');  // access type

    // Setup table setup

  $tableName = $prefix . '_mediashare_setup';

  $pntable['mediashare_setup'] = $tableName;

  $pntable['mediashare_setup_column'] = array('id'            => 'ms_id',           // Unique ID
                                              'kind'          => 'ms_kind',         // 0: group, 1: user
                                              'storageLimit'  => 'ms_storagelimit', // Storage limit
                                              'unitId'        => 'ms_unitid');      // User or group id this applies to


    // Photoshare conversion table

  $tableName = $prefix . '_mediashare_photoshare';

  $pntable['mediashare_photoshare'] = $tableName;

  $pntable['mediashare_photoshare_column'] = array('photoshareImageId'      => 'msph_photoshareimageid',
                                                   'mediashareThumbnailRef' => 'msph_mediasharethumbnailref',
                                                   'mediasharePreviewRef'   => 'msph_mediasharepreviewref',
                                                   'mediashareOriginalRef'  => 'msph_mediashareoriginalref');


    // Invitations table

  $tableName = $prefix . '_mediashare_invitation';

  $pntable['mediashare_invitation'] = $tableName;

  $pntable['mediashare_invitation_column'] = array('id'         => 'msinv_id',
                                                   'created'    => 'msinv_created',
                                                   'albumId'    => 'msinv_albumid',
                                                   'key'        => 'msinv_key',
                                                   'viewCount'  => 'msinv_count',
                                                   'email'      => 'msinv_email',
                                                   'subject'    => 'msinv_subject',
                                                   'text'       => 'msinv_text',
                                                   'sender'     => 'msinv_sender',
                                                   'expires'    => 'msinv_expires');

  return $pntable;
}
