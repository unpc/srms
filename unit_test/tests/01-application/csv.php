<?php
/*
 * @file csv.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试不同系统下，CSV基类读写功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/csv

注：前提：创建csv对象，实现类属性的初始化

函数：write(array $arr)
	1.测试数据：
	     __construct('csv.txt', 'r', NULL)
		输入参数：array('name','age','sex','salary')
		输出：FALSE
		
	2.测试数据：(windows系统)
	     浏览器系统为windows:$_SERVER['HTTP_USER_AGENT']=Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16',
	     __construct('csv.txt', 'w', NULL)
		输入参数：array('name','age','sex','salary')
		输出：无
		(
			$t = "\t";
			$n = "\r\n";
			期望值 = trim(implode($t, array('name','age','sex','salary')) . $n);
		)
					
	3.测试数据：(非windows系统)
		浏览器系统为windows:$_SERVER['HTTP_USER_AGENT']=Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16',
	     __construct('csv.txt', 'w', NULL)
		输入参数：array('name','age','sex','salary')
		输出：无（期望值:'name,age,sex,salary'）
函数：read()
	1.测试出据（浏览器为Windows系统：）
		输入：通过write()函数写入array('姓名','age','sex','salary')
		输出：array('姓名','age','sex','salary','')
	2.测试出据（浏览器为非Windows系统：）
		输入：通过write()函数写入array('姓名','age','sex','salary')
		输出：array('姓名','age','sex','salary','')
*/
//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(csv)", class_exists('csv'));
Unit_Test::assert("method_exists(csv::write)", method_exists('csv', 'write'));
Unit_Test::assert("method_exists(csv::read)", method_exists('csv', 'read'));
Unit_Test::echo_endl();

class CSV_Test{
	static function init($info) {
		$_SERVER['HTTP_USER_AGENT']=$info;
	}
	//情况一
	static function test_write01($info,$filename,$mode,$content,$expect) {
		$csv=new CSV($filename,$mode);
		$output=$csv->write($content);
		Unit_Test::assert($info,$expect==$output,$output);
		$csv->close();
		@unlink($filename);
	}
	//情况二
	static function test_write02($info, $mode, $content) {

		$t = "\t";
		$n = "\r\n";
		$expect = trim(implode($t, $content) . $n);
		
		$filename = 'csv_test.out';

		$csv=new CSV($filename,$mode);
		$csv->write($content);
		$csv->close();
        

		$csv = new CSV($filename, "r");
		$output=trim(implode($t, $csv->read()));

		Unit_Test::assert($info,$expect==$output,$output);
		
		fclose($file);
		
		@unlink($filename);
	}
	
	//情况三
	static function test_write03($info, $mode, $content,$expect) {	
		$filename = 'csv_test.out';

		$csv=new CSV($filename,$mode);
		$csv->write($content);
		$csv->close();
		
		$file=fopen($filename,"r");
		
		$output=trim(fgets($file));

		Unit_Test::assert($info,$expect==$output,$output);
		
		fclose($file);
		
		@unlink($filename);
	}
	
	static function test_read($info,$mode,$content,$expect) {
	    $filename = 'csv_test.out';
	    //写入
		$csv=new CSV($filename,$mode);
		$csv->write($content);
		$csv->close();
		//读出
		$csv=new CSV($filename,'r');
        $reflector = new ReflectionMethod('CSV', 'fgets');
        $reflector->setAccessible(TRUE);
        $output = $reflector->invoke($csv);
		
		Unit_Test::assert($info,$expect==$output,$output);
		$csv->close();
        @unlink($filename);
	}

    static function test_file_data($file_path, $result) {
        $file = Unit_Test::data_path('csv/'.$file_path);
        $csv = new CSV($file, 'r');
        Unit_Test::echo_title(sprintf("\n".'CSV::data测试(测试文件 %s ):', $file_path));
        $i = 0;
        foreach($result as $r) {
            $read = $csv->read();
            Unit_Test::assert(sprintf('第%d行测试', $i),$r == $read, $read);
            $i ++;
        }
    }
}

Unit_Test::echo_title('CSV::write测试:');
    $tmp = ['name', 'age', 'sex', 'salary'];

    CSV_Test::test_write01('情况一：','csv.txt','r',$tmp,FALSE);

    CSV_Test::init('Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16');
    CSV_Test::test_write02('情况二：', 'w', $tmp);

    CSV_Test::init('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8');
    CSV_Test::test_write03('情况三：','w',$tmp,'name,age,sex,salary');

Unit_Test::echo_endl();

Unit_Test::echo_title('CSV::read测试:');
    CSV_Test::init('Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16');
    CSV_Test::test_read('浏览器为Windows系统：','w',['姓名','age','sex','salary'], "姓名\tage\tsex\tsalary");

    CSV_Test::test_read('浏览器为非Windows系统：','w',["foobar",'fo\nobar','"test','news'], "foobar\tfo\\nobar\t\"\"\"test\"\tnews");

    CSV_Test::init('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8');
    CSV_Test::test_read('浏览器为非Windows系统：','w',['姓名','age','sex','salary'], '姓名,age,sex,salary');
    CSV_Test::test_read('浏览器为非Windows系统：','w',['"\nfoobar"','fo\nobar','"test','news'], "\"\"\"\\nfoobar\"\"\",\"fo\\nobar\",\"\"\"test\",news");

    CSV_Test::test_read('浏览器为非Windows系统：','w',["\nfoobar",'fo\nobar','"test','news'], "\"\nfoobar\",\"fo\\nobar\",\"\"\"test\",news");

    //设定为linux下文件读取
    CSV_Test::init('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8');

    CSV_Test::test_file_data('linux/without_newline_without_quote.csv', [
        [
            'line1',
            'first line'
        ],
        [
            'line2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('linux/without_newline_with_quote.csv', [
        [
            'line1',
            'first"line'
        ],
        [
            'line"2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('linux/with_newline_without_quote.csv', [
        [
            'line1',
            "first\nline"
        ],
        [
            'line2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('linux/with_newline_with_quote.csv', [
        [
            'line1',
            "first\n\"line" //请注意 \"是php转义语法，与CSV无关
        ],
        [
            'line2',
            'second line"'
        ]
    ]);
    CSV_Test::test_file_data('linux/with_comma.csv', [
        [
            'line1',
            "first,,\n\"line" //请注意 \"是php转义语法，与CSV无关
        ],
        [
            'line2',
            'second,line,"'
        ]
    ]);

    //设定为windows下文件读取检测
    CSV_Test::init('Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16');

    CSV_Test::test_file_data('win/without_newline_without_quote.csv', [
        [
            'line1',
            'first line'
        ],
        [
            'line2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('win/without_newline_with_quote.csv', [
        [
            'line1',
            'first"line'
        ],
        [
            'line"2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('win/with_newline_without_quote.csv', [
        [
            'line1',
            "first\nline"
        ],
        [
            'line2',
            'second line'
        ]
    ]);

    CSV_Test::test_file_data('win/with_newline_with_quote.csv', [
        [
            'line1',
            "first\n\"line" //请注意 \"是php转义语法，与CSV无关
        ],
        [
            'line2',
            'second line"'
        ]
    ]);

    CSV_Test::test_file_data('win/zh_without_quote.csv', [
        [
            'line1',
            "中文\n换行"
        ],
    ]);

    CSV_Test::test_file_data('win/zh_with_quote.csv', [
        [
            'line1',
            "中文\n\"换行" //请注意 \"是php转义语法，与CSV无关
        ],
    ]);
    CSV_Test::test_file_data('win/with_comma.csv', [
        [
            'line1',
            "first\t\t\"\nline" //请注意 \"是php转义语法，与CSV无关
        ],
        [
            'line2',
            "second\tline\t\""
        ]
    ]);

Unit_Test::echo_endl();
