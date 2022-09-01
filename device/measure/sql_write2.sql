CREATE TABLE `__TABLE_NAME__` (
  `dta_idx` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '데이터idx',
  `dta_type` tinyint(4) DEFAULT 0 COMMENT '데이터타입',
  `dta_no` int(11) DEFAULT 0 COMMENT '데이터번호',
  `dta_value` double DEFAULT 0 COMMENT '테이터값',
  `dta_1` int(11) DEFAULT 0 COMMENT '추가정보1',
  `dta_2` int(11) DEFAULT 0 COMMENT '추가정보2',
  `dta_dt` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '일시',
  PRIMARY KEY (`dta_idx`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;