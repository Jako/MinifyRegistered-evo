History
================================================================================

- 0.2.6
    - new: exclude css files from minify
- 0.2.5
    - new: external css files are not minified
- 0.2.4
    - replace chunks before setting markers
- 0.2.3
    - using markers instead of removing registered scripts directly
- 0.2.2
    - bugfix: conditional lines are ignored 
- 0.2.1
    - bugfix: external sources are included first 
- 0.2 
    - rewritten on base of minifyRegistered Revo version
    - OnLoadWebDocument event removed
    - works with cached and uncached documents
- 0.1.3 
    - bugfix: minify javascripts at the end ot the body
- 0.1.2 
    - bugfix: in some conditions scripts are inserted without script tag
    - new: external javascripts are not minified
    - new: excludeJs parameter
- 0.1.1
    - bugfix: the template variables were parsed without any error (blank page)
    - new: groupJs parameter
- 0.1
    - proof of concept
