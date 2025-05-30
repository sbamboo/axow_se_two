Directives:
  %<path>%                             //SameArticleRetrive
  @<prof>                              //ProfileLink
  $<cat>:<subcat>/<art>                //ArticleLink
  $<cat>:<subcat>/<art>/<path>         //ArticleAssetFetch
  $<cat>:<subcat>/<art>:<attribute_id> //ArticleFetch
  MERGE:FROM                           //Merge.From (If this is key merge with path from value)
  !<text>!                             //Placeholder (Unparsed)

Constants:
  MARKDOWN.__axo77_bluemap__ = "test.bluemap.axow.se"

DataTypeDescriptors:
  AttrID    -> Literal
  MediaType -> Literal
DataTypes:
  *         -> SameArticleRetrive / ArticleFetch
  TEXT      -> Literal; {ProfileLink, ArticleLink}
  URL       -> Literal / ArticleAssetFetch / ProfileLink / ArticleLink
  MARKDOWN  -> Literal; {ProfileLink, ArticleLink}
  DATETIME  -> Literal
  TIME      -> [period => URL,  date => DATETIME]
  MEDIA     -> [type => <MediaType>,  media => URL,  alt => TEXT,  desc => MARKDOWN]
  REFERENCE -> [id => Literal,  text => TEXT,  href => URL,  time => DATETIME]
  COORD     -> List<[Literal/Int],*>
  PROFILES  -> LIST<ProfileLink,ArticleLink>
  DESC_PROFILES -> List< Pair<(ProfileLink,ArticleLink), TEXT> >

SetTypes:
  ROOT.group
  ROOT.category
  ROOT.page

  META.title
  META.short_description    

  INFOBOX.*
    attribute_id (opt) -> AttrID
  INFOBOX.metadata
    value       -> MULTITYPE{"value_type"}
    value_type  -> <lowerCase(DataType)>
  INFOBOX.title
    content     -> TEXT
  INFOBOX.banner
    content     -> URL
  INFOBOX.banner_source
    content     -> MARKDOWN
  INFOBOX.attribute
    title       -> TEXT
    content     -> MARKDOWN
  INFOBOX.attributed_profile
    title       -> TEXT
    profiles    -> PROFILES
  INFOBOX.header
    content     -> TEXT
  INFOBOX.attributed_location
    title       -> TEXT
    content     -> COORD
  INFOBOX.named_multivalue_attribute
    title       -> TEXT
    content     -> [
                    name  => TEXT,
                    type  => <lowerCase(DataType)>,
                    value => MULTITYPE{"type"},
                    attribute_id => AttrID,
                  ]
  INFOBOX.attributed_descripted_profiles
    title       -> TEXT
    value       -> DESC_PROFILES
  INFOBOX.attributed_time
    title       -> TEXT
    time        -> TIME
    description -> MARKDOWN

  SECTIONS.*
    section_id (opt) -> AttrID  
  SECTIONS.introduction
    content     -> MARKDOWN
  SECTIONS.text
    title       -> TEXT
    content     -> MARKDOWN
  SECTIONS.media_grid
    title       -> TEXT
    content     -> List<MEDIA>
  SECTIONS.sources
    title       -> TEXT
    content     -> List<REFERENCE>
  SECTIONS.timeline
    title       -> TEXT
    content     -> Dictionary<DATETIME, [title => TEXT,  description => MARKDOWN]>
  SECTIONS.img-table
    title       -> TEXT
    content     -> {
                     "columns": List<TEXT>,
                     "entries": List< [<lowerCase(DataType)>, *] >
                   }
  COMMENT
    by          -> ProfileLink / ArticleLink
    title (opt) -> TEXT
    content     -> MARKDOWN
    posted      -> DATETIME
    note (opt)  -> TEXT


MEDIA
  image
  video
  link
  embed
  audio
  wiki