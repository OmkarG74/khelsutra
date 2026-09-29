import 'package:flutter/material.dart';
import 'app/app.dart';
import 'core/storage/token_storage.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await TokenStorage.init();
  runApp(const KhelSutraApp());
}
