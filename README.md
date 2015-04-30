#AWS CloudFront hook

WordPress MU plugin

###Required
* [s3fs](https://github.com/s3fs-fuse/s3fs-fuse) mounted as `/wp-content/uploads` dir

##Usage
* move cloudfront.php file to `wp-content/mu-plugins` dir
* add line `define( 'AWS_CLOUDFRONT_DOMAIN', 'xxxxxx.cloudfront.net' );` to wp-config.php