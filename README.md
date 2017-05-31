# ReCal

ReCal is an intercoder reliability engine written in PHP. It currently computes the following coefficients:

* Pairwise percent agreement
* Scott's pi
* Cohen's kappa
* Krippendorff's alpha (nominal, ordinal, interval, and ratio variants)

In a nutshell, ReCal accepts CSV files formatted according to the instructions [here](http://dfreelon.org/utils/recalfront/recal2/) and yields results in HTML format. Results from multiple calculations can be saved in a single session, and for ReCal2 and 3 they can be exported to CSV format. A free, hosted version of the program is available [at my website](http://dfreelon.org/utils/recalfront/) along with more detailed instructions on its use.

ReCal consists of three interface files (```recal2.php```, ```recal3.php```, and ```recal-oir.php```) and one library file (```recal-lib.php```). The interface files implement the numerical calculations, while the library file handles most of the data preprocessing, error-checking, and visual formatting. Anyone wishing to extend this project might find the library useful--I refined it based on user input over the course of several years.

ReCal should be compatible with PHP v5 and higher. To install it, simply place the four PHP files in a directory where PHP is enabled with file-writing permissions. Data files can be submitted by viewing the appropriate interface file in a web browser.

These files are presented as-is with minimal inline comments. I make no guarantee of support for installing ReCal or building on it, but depending on what you want to do, I might be able to help.
