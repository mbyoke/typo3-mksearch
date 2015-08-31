v1.4.35 (??.??.2015)
 * Predefined form field for sorting search results in solr
 * Indexer configuration: It is possible to index database fields into more several document attributes
 * Predefined form field to limit page size for solr

2010-01-07 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.3.2
	* Cleaned up generic searches - thx to rn_base 0.6.1
	* tx_mksearch_filter_SearchByReferer: Include fe_groups 
	  of current FE user into search query
	* Reactivated index update functionality and advanced
	  search queries which were disabled in v0.1.2a
	
2010-01-05 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.3.1
	* Extended functionality of indexer base class
	  tx_mksearch_service_indexer_BaseDataBase
	* Added specialized indexers for core.page and
	  core.tt_content which consider special
	  TemplaVoilà issues (e.g. page shortcuts)

2009-12-23 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.3.0
	* Splitted former indexer core.tt_content into
	  two separate indexers for pages and content elements:
	  * These now include effectively calculated fe groups.
	  * Definition of whitelists and blacklists now possible.
	  * Largely extended configuration possibilities.
	* New method tx_mksearch_model_IndexerDocument->setFeGroups():
	  Use this method optionally to store fe groups of the
	  indexed item.
	* tx_mksearch_filter_SearchForm: Include fe_groups
	  of current FE user into search query
	* Made tx_mksearch_service_engine_ZendLucene->search()
	  aware of fe_groups by narrowing down the given search
	  if fe_groups are passed.
	* CLI indexer script:
	  * Optical improvements of shell indexing script
	  * It's now possible to use several configuration files.
	* Restricted downward compatibility due to
	  * changed interface of tx_mksearch_model_IndexerDocument->addField()
	  * no longer existing interface tx_mksearch_interface_IndexerDocument
	    => derive from tx_mksearch_model_IndexerDocument instead!
	
2009-12-17 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.2.3
	* Made indexer tt_news.news more configurable
	  (news category blacklist)
	* Memory consumption optimization
	  (added cleanup function to indexer interface)
	
2009-12-16 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.2.2
	* Bugfixes
	* Indexer for tt_news
	
2009-12-10 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.2.1
	* Bugfixes
	* Made indexer core.tt_content more configurable
	  and sensible for hidden content element headers
	
2009-12-10 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.2.0
	* Corrected charset encoding problems
	* Restricted downward compatibility due to refactored
	  indexing process:
	  -> use new model tx_mksearch_model_IndexerField instead
	    of plain data array 
	
2009-11-02 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.1.3
	* Added filter which parses the HTTP referer for search term
	* Misc optimizations
	
2009-10-30 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.1.2a
	* Disabled index update functionality

2009-10-28 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.1.2
	* Extensive optimizations

2009-10-27 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.1.1
	* Changed handling of configuration

2009-10-26 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.1.0
	* First actually usable version
	* Fixed localisation / encoding issues for special chars

2009-10-22 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.0.4
	* Empowered index engine to use several indices and
	  to configure the indexers according to the used index.

2009-10-21 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.0.3
	* Indexer: Conceptual optimization: extended usage of inversion of control

2009-10-16 Lars Heber <lars.heber@das-medienkombinat.de>
	* v0.0.2
	* Indexer: Implementation of command pattern

2009-09-14 Lars Heber <lars.heber@das-medienkombinat.de>
	* initial code generated with kickstarter