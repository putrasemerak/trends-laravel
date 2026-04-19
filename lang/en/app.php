<?php
// ============================================================
// ENGLISH translation file
// ============================================================
return [

    // ---- Navigation ----
    'nav_dashboard'  => 'Dashboard',
    'nav_programs'   => 'Programs',
    'nav_upload'     => 'Upload',
    'nav_logout'     => 'Logout',
    'nav_bioburden'        => 'Bioburden',
    'nav_bb_dashboard'     => 'Trending Analysis',
    'nav_bb_upload'        => 'Data Upload',
    'nav_em'               => 'Environmental Monitoring',
    'nav_em_dashboard'     => 'Trending Analysis',
    'nav_em_upload'        => 'Data Upload',
    'em_title'             => 'Environmental Monitoring — Trending Overview',
    'em_upload_title'      => 'Environmental Monitoring Data Upload',
    'em_no_data'           => 'No data available. Upload EM Excel files to get started.',
    'em_upload_desc'       => 'Select an Environmental Monitoring Excel file (.xls). All sheets will be parsed automatically.',
    'em_machine'           => 'Machine / Area',
    'em_personnel'         => 'Personnel Hygiene',
    'em_surface'           => 'Surface & Rinse',
    'em_air'               => 'Air Quality (SP & AS)',
    'em_filling'           => 'Filling Room',
    'em_packing'           => 'Packing Room',
    'em_std'               => 'Standard Limit',
    'em_action'            => 'Action Limit',
    'em_alert'             => 'Alert Limit',
    'em_na'                => 'N/A',
    'em_fdab'              => 'Finger Dab (F/dab)',
    'em_garment'           => 'Garment (Gar)',
    'em_wall'              => 'Wall',
    'em_floor'             => 'Floor',
    'em_machine_surf'      => 'Machine',
    'em_nozzle'            => 'Filling Nozzle',
    'em_settle_plate'      => 'Settle Plate (SP)',
    'em_active_sampling'   => 'Active Sampling (AS)',
    'em_loc'               => 'Location',
    'em_emp_no'            => 'Employee No.',
    'em_room'              => 'Room',
    'em_import_ok'         => 'Data imported successfully.',
    'em_import_skip'       => 'rows skipped (duplicates)',
    'em_anomaly_ph'        => 'Personnel Hygiene Anomalies',
    'em_anomaly_surf'      => 'Surface Anomalies',
    'em_anomaly_air'       => 'Air Quality Anomalies',
    'bb_upload_title'      => 'Bioburden Data Upload',
    'bb_upload_desc'       => 'Select a Bioburden Excel file (.xlsx / .xls). All sheets will be scanned automatically.',


    // ---- Greeting bar ----
    'greeting'       => 'Welcome',

    // ---- Login page ----
    'login_subtitle'  => 'Bioburden Trending Analysis System',
    'login_title'     => 'Sign In',
    'login_username'  => 'Employee ID',
    'login_password'  => 'Password',
    'login_btn'       => 'Sign In',
    'login_footer'    => 'Authorised personnel only.',

    // ---- Dashboard ----
    'dash_title'     => 'Dashboard — Bioburden Trending Overview',
    'dash_no_data'   => 'No data available yet. Upload test results to get started.',
    'dash_samples'   => 'Samples',
    'dash_avg'       => 'Avg',
    'dash_max'       => 'Max',

    // ---- Dashboard Detail ----
    'detail_all'        => 'All Products',
    'detail_total'      => 'Total Samples',
    'detail_avg'        => 'Average CFU',
    'detail_max'        => 'Max CFU',
    'detail_latest'     => 'Latest Test',
    'detail_monthly'    => 'Monthly Trend (12 Months)',
    'detail_batch'      => 'Batch Results (Last 60 Days)',
    'detail_recent'     => 'Recent Entries',

    // ---- Upload page ----
    'upload_title'      => 'Upload Bioburden Data',
    'upload_info'       => 'No reformatting needed. Upload your original monthly monitoring Excel file. The system reads all sheets automatically — each sheet is one production line.',
    'upload_label'      => 'Select Monitoring Excel File',
    'upload_drop'       => 'Drag & drop your file here, or',
    'upload_browse'     => 'browse',
    'upload_accepted'   => 'Accepted: .xlsx, .xls | Max 10MB',
    'upload_remove'     => 'Remove',
    'upload_preview'    => 'Preview Data',
    'upload_change'     => 'Change File',
    'upload_confirm'    => 'Confirm & Save to Database',
    'upload_new'        => 'New Upload',

    // ---- Upload results table ----
    'res_sheet'         => 'Sheet',
    'res_line'          => 'Line',
    'res_inserted'      => 'Inserted',
    'res_duplicate'     => 'Duplicate',
    'res_incomplete'    => 'Incomplete',
    'res_status'        => 'Status',
    'res_total'         => 'Total',
    'res_import_title'  => 'Import Results',
    'res_dupe_label'    => 'Duplicate rows (already in DB)',

    // ---- Status labels ----
    'status_ok'         => 'OK',
    'status_partial'    => 'Partial',
    'status_skipped'    => 'Skipped',
    'status_no_data'    => 'No Data',

    // ---- Table columns (kept close to lab terminology) ----
    'col_date'          => 'Date',
    'col_product'       => 'Product',
    'col_batch'         => 'Batch',
    'col_run'           => 'Run',
    'col_avg'           => 'Avg',
    'col_added_by'      => 'Added By',

    // ---- General ----
    'btn_back'          => 'Back',
    'lbl_preview'       => 'Preview',
    'lbl_saving'        => 'Saving...',

    // ---- Upload overlays ----
    'lbl_loading_charts'   => 'Loading charts...',
    'ovl_scan_title'        => 'Scanning File',
    'ovl_scan_reading'      => 'Reading sheets...',
    'ovl_scan_header'       => 'Parsing header rows...',
    'ovl_scan_layout'       => 'Detecting column layout...',
    'ovl_scan_extract'      => 'Extracting data rows...',
    'ovl_scan_prepare'      => 'Preparing preview...',
    'ovl_scan_done'         => 'Done!',
    'ovl_save_title'        => 'Saving to Database',
    'ovl_save_uploading'    => 'Uploading file...',
    'ovl_save_processing'   => 'Processing data...',
    'ovl_save_complete'     => 'Import complete!',
    'ovl_save_done'         => 'All Done!',
    'ovl_err_read'          => 'Failed to read file. Please try again.',
    'ovl_err_server'        => 'Server returned an error. Please try again.',
    'ovl_err_response'      => 'Unexpected response from server. Please try again.',
    'ovl_err_network'       => 'Network error during import. Please try again.',
    'lbl_rows_across'       => 'rows across',
    'lbl_sheets'            => 'sheet(s)',
    'lbl_anomaly'           => 'anomaly',
    'lbl_loading_records'   => 'Loading records...',
    'lbl_failed_load'       => 'Failed to load records.',
    'lbl_search'            => 'Search:',
    'lbl_show'              => 'Show _MENU_',
    'lbl_info'              => '_START_–_END_ of _TOTAL_ records',
];
