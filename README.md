Add this to service.yml

````
nelmio_api_doc.extractor.api_doc_extractor:
        class: Opstalent\DocBundle\Extractor\ApiDocExtractor
        arguments: ['@service_container', '@router', '@annotation_reader', '@nelmio_api_doc.doc_comment_extractor', '@nelmio_api_doc.controller_name_parser', {  }, {  }]
````