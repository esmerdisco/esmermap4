package com.esmermap.app

import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.content.Intent
import android.os.Bundle
import android.view.animation.AccelerateDecelerateInterpolator
import android.widget.ImageView
import androidx.appcompat.app.AppCompatActivity

class SplashActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        val logo = findViewById<ImageView>(R.id.logo)
        val flower = findViewById<ImageView>(R.id.flower)

        // Initial states
        logo.alpha = 0f
        flower.alpha = 0f
        flower.scaleX = 0.2f
        flower.scaleY = 0.2f

        val logoFadeIn = ObjectAnimator.ofFloat(logo, "alpha", 0f, 1f).apply {
            duration = 400
        }

        val flowerFadeIn = ObjectAnimator.ofFloat(flower, "alpha", 0f, 1f).apply {
            duration = 260
            startDelay = 350
        }

        val flowerScaleX = ObjectAnimator.ofFloat(flower, "scaleX", 0.2f, 1.15f, 1.0f).apply {
            duration = 850
            startDelay = 350
        }

        val flowerScaleY = ObjectAnimator.ofFloat(flower, "scaleY", 0.2f, 1.15f, 1.0f).apply {
            duration = 850
            startDelay = 350
        }

        val root = findViewById<android.view.View>(android.R.id.content)
        val allFadeOut = ObjectAnimator.ofFloat(root, "alpha", 1f, 0f).apply {
            duration = 220
            startDelay = 1350
        }

        val set = AnimatorSet().apply {
            interpolator = AccelerateDecelerateInterpolator()
            playTogether(logoFadeIn, flowerFadeIn, flowerScaleX, flowerScaleY, allFadeOut)
        }

        set.addListener(object : android.animation.AnimatorListenerAdapter() {
            override fun onAnimationEnd(animation: android.animation.Animator) {
                startActivity(Intent(this@SplashActivity, MainActivity::class.java))
                finish()
                overridePendingTransition(0, 0)
            }
        })

        set.start()
    }
}
