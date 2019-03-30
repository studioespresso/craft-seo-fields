---
title: Field - SEO Fields
prev: false
next: false
---
# Field

The plugin assumes that the handle of the `SEO Fields` field will be simply: __`seo`__. If it can't find that field, it won't output anything.

If you use a different handle for your field, simply copy [this file](src/config.php) to the `config` directory of your project and change the fieldhandle to your own. 

<carousel 
    :per-page="1" 
    :speed="800"
    :loop="true"
    :center-mode="true"
    :mouse-drag="true"
    :autoplay="true"
    paginationColor="#efefef"
    paginationActiveColor="#3b68b5"
    >
    <slide>
        <img src="./images/field-general.png">
    </slide>
    <slide>
        <img src="./images/field-facebook.png">
    </slide>
    <slide>
        <img src="./images/field-twitter.png">
    </slide>
</carousel>

### Field settings
In the field settings you can enable or disable the following:
- Show the general tab
- Show the facebook tab
- Show the twitter tab

Under the advanced options you can:
- Enable the option to hide the site title
- Enable the option to hide the site title