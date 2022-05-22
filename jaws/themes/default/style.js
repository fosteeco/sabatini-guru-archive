// settings for the change month & change stats dialog box
// instructions can be found here: http://malsup.com/jquery/block/#override
$.extend($.blockUI.defaults.overlayCSS,     { backgroundColor: '#000',
                                              opacity: '0.6',
                                              cursor: 'default' });
$.extend($.blockUI.defaults.pageMessageCSS, { width:'50%',
                                              margin:'0 0 0 -25%',
                                              top:'20%',
                                              left:'50%',
                                              color:'#333',
                                              backgroundColor:'#fff',
                                              padding:'20px',
                                              border:'1px solid #333',
                                              cursor: 'default',
                                              textAlign: 'left' });

// pie chart colours
var g_sColor = "0x86b4ab,0x9e9e7d,0xab812e,0xcccc9f,0xa0acba,0x8c8c85,0xe2e2d8";
var g_sShadowColor = "0xffffff";